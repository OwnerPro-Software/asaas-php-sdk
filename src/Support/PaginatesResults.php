<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

trait PaginatesResults
{
    /**
     * Hard ceiling on the pages one `all()` will fetch.
     *
     * The two diagnostic backstops each need a signal the envelope may withhold:
     * `totalCount` is 0 when omitted, and the stall check needs a page carrying
     * exactly the rows of the one before it. An endpoint that ignores `offset`
     * *and* answers in an unstable order satisfies neither — it just keeps
     * saying `hasMore` — and the walk would run until the process died.
     *
     * The ceiling counts **pages**, so the row it stops at is `MAX_PAGES` times
     * whatever `limit` the caller chose: a million rows at the API's maximum
     * page size of 100, a tenth of that at `limit: 10`. A million is well past
     * any real Asaas account, but a small page size can put the ceiling in
     * front of a set the walk would otherwise finish — which is why
     * {@see self::runawayWalk()} says so rather than blaming the endpoint.
     *
     * The exact number is a policy choice rather than behaviour — a walk is
     * bounded at 9 999 or 10 001 just as well — so the value itself is not
     * mutated. That there *is* a ceiling, and that reaching it reports
     * `PAGINATION_RUNAWAY` rather than stopping quietly, is pinned by test.
     */
    private const int MAX_PAGES = 10_000; // @pest-mutate-ignore

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

        $nextPageFetcher = fn (int $offset): AsaasPaginatedResult => $this->paginate(
            $path,
            array_merge($query, ['offset' => $offset]),
        );

        /** @var RawResponse $rawResponse */
        $rawResponse = $asaasResult->response;

        // Read through the envelope rather than off the raw body: the fields
        // below are typed, and an upstream that answers 200 with a shape none
        // of them can hold must not throw a TypeError out of the SDK.
        $paginationEnvelope = PaginationEnvelope::from($asaasResult->data ?? [], $rawResponse);

        return AsaasPaginatedResult::success(
            data: $paginationEnvelope->data,
            totalCount: $paginationEnvelope->totalCount,
            hasMore: $paginationEnvelope->hasMore,
            limit: $paginationEnvelope->limit,
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
        $pages = 0;
        $previousRows = null;

        do {
            $pages += 1;

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
                if ($result->hasMore) {
                    yield self::truncatedWalk($result, $delivered);
                }

                break;
            }

            if (self::isStalled($result, $previousRows)) {
                yield self::stalledPage($result, $delivered);

                break;
            }

            foreach ($result->data as $item) {
                yield $item;
            }

            $delivered += count($result->data);
            $previousRows = $result->data;

            if (self::hasDeliveredWholeSet($result, $delivered)) {
                if ($result->hasMore) {
                    yield self::contradictedCount($result, $delivered);
                }

                break;
            }

            if ($pages >= self::MAX_PAGES) {
                yield self::runawayWalk($result, $delivered);

                break;
            }

            $result = $result->next();
        } while ($result !== null);
    }

    /**
     * The walk cannot advance: this page carries exactly the rows of the one
     * before it and still promises another. See {@see self::stalledPage()} for
     * why `hasMore` is what separates a stall from a coincidence.
     *
     * @param  ?list<array<string, mixed>>  $previousRows
     */
    private static function isStalled(AsaasPaginatedResult $asaasPaginatedResult, ?array $previousRows): bool
    {
        return $asaasPaginatedResult->hasMore && $asaasPaginatedResult->data === $previousRows;
    }

    /**
     * Every row the filters match has been handed over. See
     * {@see self::contradictedCount()} for why a `totalCount` of 0 — the value
     * an envelope omitting it reports — cannot answer this.
     */
    private static function hasDeliveredWholeSet(AsaasPaginatedResult $asaasPaginatedResult, int $delivered): bool
    {
        return $asaasPaginatedResult->totalCount > 0 && $delivered >= $asaasPaginatedResult->totalCount;
    }

    /**
     * The envelope's own count is the walk's backstop. Every domain spec
     * defines `totalCount` as "quantidade total de itens para os filtros
     * informados" — the whole filtered set, not the page — so having delivered
     * that many rows means the walk is done. `totalCount` is 0 when the
     * envelope omits it, and then there is nothing to compare against.
     *
     * Reaching it while the same response still says `hasMore: true` is the
     * envelope contradicting itself. Stopping is the only way not to loop, but
     * stopping quietly would be a truncated walk indistinguishable from a
     * complete one — the exact failure this backstop exists beside. Say so.
     */
    private static function contradictedCount(AsaasPaginatedResult $asaasPaginatedResult, int $delivered): AsaasPaginatedError
    {
        return new AsaasPaginatedError(
            [[
                'code' => 'PAGINATION_INCONSISTENT',
                'description' => sprintf(
                    'Walk stopped after %d rows, the totalCount the API reported, but the same response still set hasMore=true. The endpoint is contradicting itself — rows may be missing. Page manually with next() if you need to inspect this.',
                    $delivered,
                ),
            ]],
            $asaasPaginatedResult->response,
            $asaasPaginatedResult->offset,
            $asaasPaginatedResult->limit,
        );
    }

    /**
     * The page carries no rows while the same envelope still says there is
     * more to fetch.
     *
     * An empty page is the ordinary end of a walk, and ending there is the only
     * thing to do — there is nothing to advance past. But a page that also
     * promises another is the envelope contradicting itself, and stopping
     * quietly would leave a truncated walk indistinguishable from a complete
     * one. Same reason `PAGINATION_INCONSISTENT` exists; a different
     * contradiction, so a different code.
     */
    private static function truncatedWalk(AsaasPaginatedResult $asaasPaginatedResult, int $delivered): AsaasPaginatedError
    {
        return new AsaasPaginatedError(
            [[
                'code' => 'PAGINATION_TRUNCATED',
                'description' => sprintf(
                    'Walk stopped after %d rows: the endpoint answered offset %d with an empty page while the same response still set hasMore=true. There is nothing to advance past, so the walk ends here — but the endpoint is contradicting itself and rows may be missing. Page manually with next() if you need to inspect this.',
                    $delivered,
                    $asaasPaginatedResult->offset,
                ),
            ]],
            $asaasPaginatedResult->response,
            $asaasPaginatedResult->offset,
            $asaasPaginatedResult->limit,
        );
    }

    /**
     * The walk hit its page ceiling with no other backstop having fired.
     *
     * Reported rather than stopped quietly, for the same reason as the others:
     * a silent stop is indistinguishable from a complete walk. See
     * {@see self::MAX_PAGES} for what gets a walk here — and note the ceiling
     * counts pages, so the message must not claim the envelope withheld a
     * `totalCount`: a walk with a perfectly good count reaches the ceiling
     * first whenever that count exceeds `MAX_PAGES * limit`.
     */
    private static function runawayWalk(AsaasPaginatedResult $asaasPaginatedResult, int $delivered): AsaasPaginatedError
    {
        return new AsaasPaginatedError(
            [[
                'code' => 'PAGINATION_RUNAWAY',
                'description' => sprintf(
                    'Walk stopped after %d pages and %d rows, the page ceiling, before anything marked it complete: the endpoint never repeated a page, and the totalCount backstop did not fire — the envelope either omitted the count or reported one the walk had not delivered yet. Rows may be missing, and rows already yielded may repeat. The ceiling counts pages rather than rows, so a small limit reaches it on a set a larger one would finish: raise limit, or page manually with next().',
                    self::MAX_PAGES,
                    $delivered,
                ),
            ]],
            $asaasPaginatedResult->response,
            $asaasPaginatedResult->offset,
            $asaasPaginatedResult->limit,
        );
    }

    /**
     * The walk is standing still: the page just fetched carries exactly the
     * rows the previous one did, at a higher offset — `next()` always advances
     * the cursor by the rows just delivered — and still says there is more.
     *
     * Caught before those rows are yielded, because the `totalCount` backstop
     * fires only once as many rows as the whole filtered set have gone out, and
     * on a page shorter than that set those are the *same* rows handed over
     * repeatedly: a consumer summing a `value` field has already double-counted
     * by the time the error arrives. An envelope omitting `totalCount` reports 0
     * and never reaches that backstop at all, so this is what ends those walks.
     *
     * `hasMore` is what separates a stall from a coincidence. Two consecutive
     * pages carrying the same rows are ambiguous on their own — a sequence of
     * fixtures in a test double produces exactly the shape a stalled endpoint
     * does — but a page that says the walk is over ends it either way, and
     * warning about a walk that is terminating anyway would report a fault
     * where none is left to avoid. Only a repeat that still promises another
     * page is a walk that cannot advance.
     *
     * Reported rather than stopped quietly, for the same reason as
     * `PAGINATION_INCONSISTENT` — a silent stop is indistinguishable from a
     * complete walk.
     */
    private static function stalledPage(AsaasPaginatedResult $asaasPaginatedResult, int $delivered): AsaasPaginatedError
    {
        return new AsaasPaginatedError(
            [[
                'code' => 'PAGINATION_STALLED',
                'description' => sprintf(
                    'Walk stopped after %d rows: the endpoint answered offset %d with the same rows as the previous page, so it is ignoring the offset it was sent and the walk cannot advance. Rows may be missing. Page manually with next() if you need to inspect this.',
                    $delivered,
                    $asaasPaginatedResult->offset,
                ),
            ]],
            $asaasPaginatedResult->response,
            $asaasPaginatedResult->offset,
            $asaasPaginatedResult->limit,
        );
    }

    /** @param array<string, mixed> $query */
    private static function queryInt(array $query, string $key): int
    {
        return isset($query[$key]) && is_numeric($query[$key]) ? (int) $query[$key] : 0;
    }
}
