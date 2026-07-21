<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;
use SensitiveParameter;

final readonly class AsaasConnector implements Connector
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
            fn (): Response => $this->pendingRequest->post($path, $data),
        );
    }

    /** @param array<string, mixed> $data */
    public function put(string $path, array $data = []): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->put($path, $data),
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

        return $this->sendRequest(function () use ($path, $data, $files): Response {
            try {
                foreach ($files as $file) {
                    $this->pendingRequest->attach(
                        $file['name'],
                        $file['contents'],
                        $file['filename'] ?? null,
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
