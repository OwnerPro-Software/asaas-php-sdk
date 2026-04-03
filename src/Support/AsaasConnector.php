<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Closure;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;

final readonly class AsaasConnector implements Connector
{
    public function __construct(private PendingRequest $pendingRequest) {}

    public static function forStandalone(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout): self
    {
        return self::make(new PendingRequest, $apiKey, $environment, $timeout);
    }

    public static function forLaravel(#[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout): self
    {
        return self::make(Http::createPendingRequest(), $apiKey, $environment, $timeout);
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

    /** @param array<string, mixed> $query */
    public function paginate(string $path, array $query): AsaasPaginatedResult
    {
        $asaasResult = $this->sendRequest(
            fn (): Response => $this->pendingRequest->get($path, $query),
        );

        if (! $asaasResult->success) {
            return AsaasPaginatedResult::failure($asaasResult->errors ?? [], $asaasResult->response);
        }

        /** @var array{data?: list<array<string, mixed>>, totalCount?: int, hasMore?: bool, limit?: int, offset?: int} $data */
        $data = $asaasResult->data ?? [];

        $nextPageFetcher = fn (int $offset): AsaasPaginatedResult => $this->paginate(
            $path,
            array_merge($query, ['offset' => $offset]),
        );

        /** @var RawResponse $rawResponse */
        $rawResponse = $asaasResult->response;

        return AsaasPaginatedResult::success(
            data: $data['data'] ?? [],
            totalCount: $data['totalCount'] ?? 0,
            hasMore: $data['hasMore'] ?? false,
            limit: $data['limit'] ?? 0,
            offset: $data['offset'] ?? 0,
            rawResponse: $rawResponse,
            nextPageFetcher: $nextPageFetcher,
        );
    }

    /**
     * Lazy iterator that auto-paginates through all pages.
     *
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(string $path, array $filters): Generator
    {
        $offset = 0;
        $limit = is_int($filters['limit'] ?? null) ? max(1, $filters['limit']) : 100;

        do {
            $result = $this->paginate(
                $path,
                array_merge($filters, ['offset' => $offset, 'limit' => $limit]),
            );

            if (! $result->success) {
                yield new AsaasPaginatedError(
                    $result->errors ?? [],
                    $result->response,
                    $offset,
                    $limit,
                );

                return;
            }

            foreach ($result->data as $item) {
                yield $item;
            }

            if ($result->data === []) {
                break;
            }

            $offset += $limit;
        } while ($result->hasMore);
    }

    private static function make(PendingRequest $pendingRequest, #[SensitiveParameter] string $apiKey, Environment|string $environment, int $timeout): self
    {
        $environment = $environment instanceof Environment ? $environment : Environment::from($environment);

        return new self(
            $pendingRequest->baseUrl($environment->baseUrl())
                ->withHeader('access_token', $apiKey)
                ->timeout($timeout)
        );
    }

    private function sendRequest(Closure $httpCall): AsaasResult
    {
        try {
            /** @var Response $response */
            $response = $httpCall();
        } catch (ConnectionException $connectionException) {
            return AsaasResult::failure(
                [['code' => 'CONNECTION_ERROR', 'description' => $connectionException->getMessage()]],
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

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return AsaasResult::success($json, $rawResponse);
    }

    /** @return list<array{code?: string, description?: string}> */
    private function extractErrors(Response $response): array
    {
        $errors = $response->json('errors');

        if (! is_array($errors)) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => $response->body()]];
        }

        /** @var list<array{code?: string, description?: string}> $errors */
        return $errors;
    }
}
