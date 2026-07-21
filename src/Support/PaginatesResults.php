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

        $delivered = 0;

        do {
            if (! $result->success) {
                yield new AsaasPaginatedError(
                    $result->errors ?? [],
                    $result->response,
                    $result->offset,
                    $result->limit,
                );

                break;
            }

            if ($result->data === []) {
                break;
            }

            foreach ($result->data as $item) {
                yield $item;
            }

            $delivered += count($result->data);

            // The envelope's own count is the walk's backstop. Every domain
            // spec defines `totalCount` as "quantidade total de itens para os
            // filtros informados" — the whole filtered set, not the page — so
            // having delivered that many rows means the walk is done. Without
            // this, an endpoint that ignored `offset` (a real possibility on
            // the routes whose query parameters Asaas never documented) would
            // answer every request with the same non-empty page and
            // `hasMore: true`, which the empty-page check above never catches:
            // the generator would emit duplicates forever. `totalCount` is 0
            // when the envelope omits it, and then there is nothing to compare
            // against.
            if ($result->totalCount > 0 && $delivered >= $result->totalCount) {
                if ($result->hasMore) {
                    // The envelope contradicts itself: the whole filtered set has
                    // been delivered, yet the server still claims another page.
                    // Stopping is the only way not to loop, but stopping quietly
                    // would be a truncated walk indistinguishable from a complete
                    // one — the exact failure this backstop exists beside. Say so.
                    yield new AsaasPaginatedError(
                        [[
                            'code' => 'PAGINATION_INCONSISTENT',
                            'description' => sprintf(
                                'Walk stopped after %d rows, the totalCount the API reported, but the same response still set hasMore=true. The endpoint is contradicting itself — rows may be missing. Page manually with next() if you need to inspect this.',
                                $delivered,
                            ),
                        ]],
                        $result->response,
                        $result->offset,
                        $result->limit,
                    );
                }

                break;
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
