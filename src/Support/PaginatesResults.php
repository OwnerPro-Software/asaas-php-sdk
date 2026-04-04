<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

trait PaginatesResults
{
    /** @param array<string, mixed> $query */
    abstract public function get(string $path, array $query): AsaasResult;

    /** @param array<string, mixed> $query */
    public function paginate(string $path, array $query): AsaasPaginatedResult
    {
        $asaasResult = $this->get($path, $query);

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
}
