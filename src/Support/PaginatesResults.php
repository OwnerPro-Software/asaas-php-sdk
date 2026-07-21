<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

trait PaginatesResults
{
    /** @param array<string, mixed> $query */
    abstract public function get(string $path, array $query = []): AsaasResult;

    /** @param array<string, mixed> $query */
    public function paginate(string $path, array $query): AsaasPaginatedResult
    {
        $asaasResult = $this->get($path, $query);
        $requestedOffset = self::queryInt($query, 'offset');

        if (! $asaasResult->success) {
            return AsaasPaginatedResult::failure(
                $asaasResult->errors ?? [],
                $asaasResult->response,
                offset: $requestedOffset,
                limit: self::queryInt($query, 'limit'),
            );
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
            // The offset we asked for, not the echoed one: it is always
            // present, whereas an envelope omitting `offset` would pin the
            // cursor at 0 and make `next()` re-request page one forever.
            offset: $requestedOffset,
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
        /** @var int|string|null $rawLimit */
        $rawLimit = $filters['limit'] ?? null;
        $limit = $rawLimit !== null ? max(1, (int) $rawLimit) : 100;

        $result = $this->paginate(
            $path,
            array_merge($filters, ['offset' => 0, 'limit' => $limit]),
        );

        do {
            if (! $result->success) {
                yield new AsaasPaginatedError(
                    $result->errors ?? [],
                    $result->response,
                    $result->offset,
                    $result->limit,
                );

                return;
            }

            if ($result->data === []) {
                break;
            }

            foreach ($result->data as $item) {
                yield $item;
            }

            $result = $result->next();
        } while ($result !== null);
    }

    /** @param array<string, mixed> $query */
    private static function queryInt(array $query, string $key): int
    {
        return isset($query[$key]) && is_numeric($query[$key]) ? (int) $query[$key] : 0;
    }
}
