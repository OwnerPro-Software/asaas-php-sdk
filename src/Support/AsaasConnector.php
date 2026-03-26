<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class AsaasConnector
{
    private readonly PendingRequest $pendingRequest;

    public function __construct(string $apiKey, string $environment, int $timeout)
    {
        if (! in_array($environment, ['sandbox', 'production'], true)) {
            throw new InvalidArgumentException(
                sprintf("Environment must be 'sandbox' or 'production', got '%s'.", $environment)
            );
        }

        $baseUrl = $environment === 'production'
            ? 'https://api.asaas.com'
            : 'https://api-sandbox.asaas.com';

        $this->pendingRequest = Http::baseUrl($baseUrl)
            ->withHeader('access_token', $apiKey)
            ->timeout($timeout);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  class-string<BaseDTO>  $dtoClass
     */
    public function get(string $path, array $query, string $dtoClass): AsaasResult
    {
        $response = $this->pendingRequest->get($path, $query);

        return $this->toResult($response, $dtoClass);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<BaseDTO>  $dtoClass
     */
    public function post(string $path, array $data, string $dtoClass): AsaasResult
    {
        $response = $this->pendingRequest->post($path, $data);

        return $this->toResult($response, $dtoClass);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<BaseDTO>  $dtoClass
     */
    public function put(string $path, array $data, string $dtoClass): AsaasResult
    {
        $response = $this->pendingRequest->put($path, $data);

        return $this->toResult($response, $dtoClass);
    }

    /** @param class-string<BaseDTO> $dtoClass */
    public function delete(string $path, string $dtoClass): AsaasResult
    {
        $response = $this->pendingRequest->delete($path);

        return $this->toResult($response, $dtoClass);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  class-string<BaseDTO>  $dtoClass
     */
    public function paginate(string $path, array $query, string $dtoClass): AsaasPaginatedResult
    {
        $response = $this->pendingRequest->get($path, $query);

        if ($response->failed()) {
            return AsaasPaginatedResult::failure(
                $this->extractErrors($response),
                $response->status(),
            );
        }

        /** @var array{data?: list<array<string, mixed>>, totalCount?: int, hasMore?: bool, limit?: int, offset?: int} $json */
        $json = $response->json();

        /** @var list<BaseDTO> $data */
        $data = array_map(
            fn (array $item): BaseDTO => new $dtoClass($item),
            $json['data'] ?? [],
        );

        $nextPageFetcher = fn (int $offset): AsaasPaginatedResult => $this->paginate(
            $path,
            array_merge($query, ['offset' => $offset]),
            $dtoClass,
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
     * @template T of BaseDTO
     *
     * @param  array<string, mixed>  $filters
     * @param  class-string<T>  $dtoClass
     * @return Generator<int, T>
     */
    public function all(string $path, array $filters, string $dtoClass): Generator
    {
        $offset = 0;
        $limit = is_int($filters['limit'] ?? null) ? $filters['limit'] : 100;

        do {
            $result = $this->paginate(
                $path,
                array_merge($filters, ['offset' => $offset, 'limit' => $limit]),
                $dtoClass,
            );

            $result->throw();

            /** @var list<T> $data */
            $data = $result->data;

            foreach ($data as $item) {
                yield $item;
            }

            $offset += $limit;
        } while ($result->hasMore);
    }

    /** @param class-string<BaseDTO> $dtoClass */
    private function toResult(Response $response, string $dtoClass): AsaasResult
    {
        if ($response->failed()) {
            return AsaasResult::failure(
                $this->extractErrors($response),
                $response->status(),
            );
        }

        /** @var array<string, mixed> $json */
        $json = $response->json();

        return AsaasResult::success(new $dtoClass($json), $response->status());
    }

    /** @return list<array{code?: string, description?: string}> */
    private function extractErrors(Response $response): array
    {
        $errors = $response->json('errors');

        if (! is_array($errors)) {
            return [];
        }

        /** @var list<array{code?: string, description?: string}> $errors */
        return $errors;
    }
}
