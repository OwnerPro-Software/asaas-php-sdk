<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use SensitiveParameter;

final readonly class AsaasConnector implements Connector
{
    use PaginatesResults;

    public function __construct(private PendingRequest $pendingRequest) {}

    public static function forStandalone(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout = 10): self
    {
        return self::make(new PendingRequest, $apiKey, $environment, $timeout, $connectTimeout);
    }

    public static function forLaravel(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout = 10): self
    {
        return self::make(Http::createPendingRequest(), $apiKey, $environment, $timeout, $connectTimeout);
    }

    /** @param array<string, mixed> $query */
    public function get(string $path, array $query): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->get($path, $query),
        );
    }

    /** @param array<string, mixed> $data */
    public function post(string $path, array $data): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->post($path, $data),
        );
    }

    /** @param array<string, mixed> $data */
    public function put(string $path, array $data): AsaasResult
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

    private static function make(PendingRequest $pendingRequest, #[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout, int $connectTimeout): self
    {
        if ($apiKey === '') {
            throw new InvalidArgumentException('The API key must not be empty.');
        }

        $environment = $environment instanceof Environment ? $environment : Environment::from($environment);

        return new self(
            $pendingRequest->baseUrl($environment->baseUrl())
                ->withHeader('access_token', $apiKey)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withOptions(['verify' => true])
        );
    }

    private function sendRequest(Closure $httpCall): AsaasResult
    {
        try {
            /** @var Response $response */
            $response = $httpCall();
        } catch (ConnectionException) {
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
                $this->extractErrors($response),
                $rawResponse,
            );
        }

        $json = $response->json();

        /** @var array<string, mixed> $data */
        $data = is_array($json) ? $json : [];

        return AsaasResult::success($data, $rawResponse);
    }

    /** @return list<array{code?: string, description?: string}> */
    private function extractErrors(Response $response): array
    {
        $errors = $response->json('errors');

        if (! is_array($errors)) {
            $body = mb_substr(strip_tags($response->body()), 0, 350);

            return [['code' => 'UNKNOWN_ERROR', 'description' => $body]];
        }

        /** @var list<array{code?: string, description?: string}> $errors */
        return $errors;
    }
}
