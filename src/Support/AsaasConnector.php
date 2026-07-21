<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use LogicException;
use SensitiveParameter;

final readonly class AsaasConnector implements Connector, Redactable
{
    use PaginatesResults;

    /**
     * @internal Construct via {@see forStandalone()} / {@see forLaravel()} —
     * they validate inputs and carry the public defaults.
     */
    public function __construct(
        private PendingRequest $pendingRequest,
        private string $baseUrl,
        // Default-argument evaluation is attributed to call sites, never to this
        // line, so a FalseToTrue mutant here is structurally unkillable — every
        // in-tree caller passes the flag explicitly. Factory defaults ARE pinned.
        private bool $throwOnTransportFailure = false, // @pest-mutate-ignore: FalseToTrue
    ) {}

    /** @return array{baseUrl: string} */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl];
    }

    /**
     * The wrapped `PendingRequest` carries the API key in its `access_token`
     * header, and neither `serialize()` nor `var_export()` honours
     * `__debugInfo()`. Refusing serialization keeps the key out of queue
     * payloads, caches and session data; `var_export()` is unguardable, so it
     * must never be pointed at a client (documented in the README).
     *
     * @return never
     */
    public function __serialize(): array
    {
        throw new LogicException(self::class.' cannot be serialized: it holds the Asaas API key, which must never reach a queue, cache, or session payload. Serialize the API key yourself (from your own secret store) and rebuild the client on the other side.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return never
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException(self::class.' cannot be unserialized.');
    }

    public static function forStandalone(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout = 10, bool $throwOnTransportFailure = false): self
    {
        return self::make(PendingRequestFactory::standalone(), $apiKey, $environment, $timeout, $connectTimeout, $throwOnTransportFailure);
    }

    public static function forLaravel(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout = 10, bool $throwOnTransportFailure = false): self
    {
        return self::make(PendingRequestFactory::laravel(), $apiKey, $environment, $timeout, $connectTimeout, $throwOnTransportFailure);
    }

    /** @param array<string, mixed> $query */
    public function get(string $path, array $query = []): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->get($path, $query),
        );
    }

    /** @param array<string, mixed> $data */
    public function post(string $path, array $data = []): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->post($path, JsonBody::of($data)),
        );
    }

    /** @param array<string, mixed> $data */
    public function put(string $path, array $data = []): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->put($path, JsonBody::of($data)),
        );
    }

    public function delete(string $path): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->delete($path),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param array<int, array{
     *     name: string,
     *     contents: string|resource,
     *     filename?: string,
     *     headers?: array<string, string>
     * }> $files
     */
    public function postMultipart(string $path, array $data, array $files = []): AsaasResult
    {
        $data = MultipartPayload::stringifyBooleans($data);

        // Both header values are validated before the first attach: a rejection
        // mid-loop would leave the already-attached files pending on the reused
        // PendingRequest and smuggle them into the next upload.
        $partNames = array_map(
            static fn (array $file): string => ContentDispositionGuard::partName($file['name']),
            $files,
        );

        $filenames = array_map(
            static fn (array $file): ?string => isset($file['filename']) ? ContentDispositionGuard::filename($file['filename']) : null,
            $files,
        );

        return $this->sendRequest(function () use ($path, $data, $files, $partNames, $filenames): Response {
            try {
                foreach ($files as $index => $file) {
                    $this->pendingRequest->attach(
                        $partNames[$index],
                        $file['contents'],
                        $filenames[$index],
                        $file['headers'] ?? [],
                    );
                }

                return $this->pendingRequest->asMultipart()->post($path, $data);
            } finally {
                // Restore the body format only. `asJson()` would also pin an explicit
                // `Content-Type: application/json` header, and Guzzle skips its own
                // `multipart/form-data; boundary=...` header when one is already set —
                // so every subsequent upload would ship a multipart body labelled JSON.
                $this->pendingRequest->bodyFormat('json');
            }
        });
    }

    private static function make(PendingRequest $pendingRequest, #[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout, bool $throwOnTransportFailure): self
    {
        if ($apiKey === '') {
            throw new InvalidArgumentException('The API key must not be empty.');
        }

        if ($timeout < 1) {
            throw new InvalidArgumentException(sprintf('Request timeout must be at least 1 second; got %d. Guzzle treats 0 as unlimited, which would let stalled connections hang indefinitely.', $timeout));
        }

        if ($connectTimeout < 1) {
            throw new InvalidArgumentException(sprintf('Connect timeout must be at least 1 second; got %d. Guzzle treats 0 as unlimited, which would let stalled TCP handshakes hang indefinitely.', $connectTimeout));
        }

        $environment = $environment instanceof Environment ? $environment : Environment::from($environment);

        return new self(
            $pendingRequest->baseUrl($environment->baseUrl())
                ->withHeader('access_token', $apiKey)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withOptions(['verify' => true]),
            $environment->baseUrl(),
            $throwOnTransportFailure,
        );
    }

    private function sendRequest(Closure $httpCall): AsaasResult
    {
        try {
            /** @var Response $response */
            $response = $httpCall();
        } catch (ConnectionException $connectionException) {
            if ($this->throwOnTransportFailure) {
                throw TransportFailureClassifier::classify($connectionException);
            }

            return AsaasResult::failure(
                [['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']],
            );
        }

        return $this->toResult($response);
    }

    private function toResult(Response $response): AsaasResult
    {
        $rawResponse = new RawResponse($response);

        if ($response->failed()) {
            return AsaasResult::failure(
                ErrorEnvelope::extract($response),
                $rawResponse,
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            // 204 No Content is a definitive success with an intentionally
            // empty body (e.g. deleteAccessToken, removeBackoff) — never an
            // unreadable-body transport failure.
            if ($this->throwOnTransportFailure && $response->status() !== 204) {
                throw new IndeterminateResultException('body', response: $rawResponse);
            }

            $json = [];
        }

        /** @var array<string, mixed> $data */
        $data = $json;

        return AsaasResult::success($data, $rawResponse);
    }
}
