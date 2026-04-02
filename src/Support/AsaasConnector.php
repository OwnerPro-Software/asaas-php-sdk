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

    /**
     * @param  array<string, mixed>  $query
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function get(string $path, array $query, string $responseClass): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->get($path, $query),
            $responseClass,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function post(string $path, array $data, string $responseClass): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->post($path, $data),
            $responseClass,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function put(string $path, array $data, string $responseClass): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->put($path, $data),
            $responseClass,
        );
    }

    /** @param class-string<BaseResponse> $responseClass */
    public function delete(string $path, string $responseClass): AsaasResult
    {
        return $this->sendRequest(
            fn (): Response => $this->pendingRequest->delete($path),
            $responseClass,
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function paginate(string $path, array $query, string $responseClass): AsaasPaginatedResult
    {
        try {
            $response = $this->pendingRequest->get($path, $query);
        } catch (ConnectionException $connectionException) {
            return AsaasPaginatedResult::failure(
                [['code' => 'CONNECTION_ERROR', 'description' => $connectionException->getMessage()]],
                0,
            );
        }

        if ($response->failed()) {
            return AsaasPaginatedResult::failure(
                $this->extractErrors($response),
                $response->status(),
            );
        }

        /** @var array{data?: list<array<string, mixed>>, totalCount?: int, hasMore?: bool, limit?: int, offset?: int} $json */
        $json = $response->json();

        /** @var list<BaseResponse> $data */
        $data = array_map(
            fn (array $item): BaseResponse => new $responseClass($item),
            $json['data'] ?? [],
        );

        $nextPageFetcher = fn (int $offset): AsaasPaginatedResult => $this->paginate(
            $path,
            array_merge($query, ['offset' => $offset]),
            $responseClass,
        );

        return AsaasPaginatedResult::success(
            data: $data,
            totalCount: $json['totalCount'] ?? 0,
            hasMore: $json['hasMore'] ?? false,
            limit: $json['limit'] ?? 0,
            offset: $json['offset'] ?? 0,
            statusCode: $response->status(),
            nextPageFetcher: $nextPageFetcher,
        );
    }

    /**
     * Lazy iterator that auto-paginates through all pages.
     *
     * @template T of BaseResponse
     *
     * @param  array<string, mixed>  $filters
     * @param  class-string<T>  $responseClass
     * @return Generator<int, T>
     */
    public function all(string $path, array $filters, string $responseClass): Generator
    {
        $offset = 0;
        $limit = is_int($filters['limit'] ?? null) ? max(1, $filters['limit']) : 100;

        do {
            $result = $this->paginate(
                $path,
                array_merge($filters, ['offset' => $offset, 'limit' => $limit]),
                $responseClass,
            );

            $result->throw();

            /** @var list<T> $data */
            $data = $result->data;

            foreach ($data as $item) {
                yield $item;
            }

            if ($data === []) {
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

    /** @param class-string<BaseResponse> $responseClass */
    private function sendRequest(Closure $httpCall, string $responseClass): AsaasResult
    {
        try {
            /** @var Response $response */
            $response = $httpCall();
        } catch (ConnectionException $connectionException) {
            return AsaasResult::failure(
                [['code' => 'CONNECTION_ERROR', 'description' => $connectionException->getMessage()]],
                0,
            );
        }

        return $this->toResult($response, $responseClass);
    }

    /** @param class-string<BaseResponse> $responseClass */
    private function toResult(Response $response, string $responseClass): AsaasResult
    {
        if ($response->failed()) {
            return AsaasResult::failure(
                $this->extractErrors($response),
                $response->status(),
            );
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return AsaasResult::success(new $responseClass($json), $response->status());
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
