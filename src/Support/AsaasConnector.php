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

    public static function forStandalone(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout = 10): self
    {
        return self::make(PendingRequestFactory::standalone(), $apiKey, $environment, $timeout, $connectTimeout);
    }

    public static function forLaravel(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout = 10): self
    {
        return self::make(PendingRequestFactory::laravel(), $apiKey, $environment, $timeout, $connectTimeout);
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
     *     filename: string,
     *     headers?: array<array-key, string|int|float|bool>
     * }> $files
     */
    public function postMultipart(string $path, array $data, array $files = []): AsaasResult
    {
        $data = MultipartPayload::stringifyBooleans(
            MultipartPayload::guardFieldNames(MultipartPayload::rejectFileParts($data)),
        );

        // Every caller-supplied header value is validated before the first
        // attach: a rejection mid-loop would leave the already-attached files
        // pending on the reused PendingRequest and smuggle them into the next
        // upload.
        $partNames = array_map(
            static fn (array $file): string => ContentDispositionGuard::partName($file['name']),
            $files,
        );

        $filenames = array_map(
            static fn (array $file): string => ContentDispositionGuard::filename(self::filenameOf($file)),
            $files,
        );

        $headers = array_map(
            static fn (array $file): array => ContentDispositionGuard::partHeaders($file['headers'] ?? []),
            $files,
        );

        return $this->sendRequest(function () use ($path, $data, $files, $partNames, $filenames, $headers): Response {
            try {
                foreach (array_keys($files) as $index) {
                    $this->pendingRequest->attach(
                        $partNames[$index],
                        $files[$index]['contents'],
                        $filenames[$index],
                        $headers[$index],
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

    private static function make(PendingRequest $pendingRequest, #[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout): self
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
                // Redirects are refused, not followed. Guzzle strips only
                // `Authorization` and `Cookie` when one crosses origins, so the
                // `access_token` header would travel to whatever host answered
                // with a `Location`; its default `protocols` allows an
                // https→http downgrade, sending the key in clear; and its
                // non-strict default replays a POST as a GET, whose 200 would
                // reach {@see ResponseInterpreter} as the POST's verdict — the
                // silent no-op CHANGELOG 2.0.0 called the real exposure of the
                // trailing-slash drift. Asaas issues no redirect, so nothing
                // legitimate is lost, and a 3xx that does arrive is reported as
                // indeterminate rather than chased.
                ->withOptions(['verify' => true, 'allow_redirects' => false]),
            $environment->baseUrl(),
        );
    }

    /**
     * An omitted filename is rejected rather than forwarded as `null`:
     * `attach()` then leaves the key out entirely, and Guzzle substitutes the
     * local file's name — past every guard here. `Connector` declares the key
     * required, but a PHPDoc shape is not enforced at runtime and this seam is
     * public, so the check has to exist as code.
     *
     * @param  array{name: string, contents: string|resource, filename?: string, headers?: array<array-key, string|int|float|bool>}  $file
     */
    private static function filenameOf(array $file): string
    {
        if (! isset($file['filename'])) {
            throw new InvalidArgumentException(sprintf(
                "Multipart part '%s' has no filename. An upload must name the file it sends, or the HTTP client falls back to the local file's name and discloses it. A part that is not a file belongs in \$data.",
                $file['name'],
            ));
        }

        return $file['filename'];
    }

    private function sendRequest(Closure $httpCall): AsaasResult
    {
        try {
            /** @var Response $response */
            $response = $httpCall();
        } catch (ConnectionException $connectionException) {
            throw TransportFailureClassifier::classify($connectionException);
        }

        return ResponseInterpreter::toResult($response);
    }
}
