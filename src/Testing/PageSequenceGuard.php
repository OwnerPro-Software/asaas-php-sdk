<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use InvalidArgumentException;

/**
 * Refuses a `stubPages()` sequence the walk cannot serve, at the point where it
 * was written rather than at request time.
 *
 * @internal Registration detail of {@see StubResponse}.
 */
final class PageSequenceGuard
{
    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  list<?list<mixed>>  $rows  each page's rows, positionally aligned
     *                                    with `$pages`, and `null` where the
     *                                    body is not a page at all — which a
     *                                    page carrying no rows is not
     * @param  list<?int>  $declaredCounts  each page's own `totalCount` — already
     *                                      proven to be an int by
     *                                      {@see PageEnvelopeGuard}, and `null`
     *                                      where the page leaves the key out
     */
    public static function validate(array $pages, array $rows, array $declaredCounts): void
    {
        self::rejectEmptySequence($pages);
        self::rejectEmptyPage($pages, $rows);
        self::rejectExhaustedCount($pages, $rows, $declaredCounts);
    }

    /** @param list<array<string, mixed>> $pages */
    private static function rejectEmptySequence(array $pages): void
    {
        if ($pages === []) {
            throw new InvalidArgumentException(
                'stubPages() requires at least one page; an empty sequence has no response to serve and would surface as an exhausted-sequence error at request time instead of here.',
            );
        }
    }

    /**
     * Rejects a page that carries no rows while other pages share the sequence.
     *
     * Neither position is servable, and both fail as a fault the endpoint never
     * committed. Every page but the last is served with `hasMore: true`, so an
     * empty one *is* the `PAGINATION_TRUNCATED` contradiction — no rows, more
     * promised — and `all()` stops there, stranding the pages behind it. Last is
     * no better: to reach it, the page carrying the final row must still say
     * `hasMore: true`, while the inferred `totalCount` already says the walk is
     * complete, and `all()` reports `PAGINATION_INCONSISTENT` on that page
     * without ever requesting the empty one.
     *
     * Alone it is the legitimate no-results fixture and stays: nothing precedes
     * it to contradict, and the walk ends on it by `hasMore: false`.
     *
     * A body that is not a page — `null` here — is opaque to the walk and holds
     * no rows to be missing, so it is untouched.
     *
     * @param  list<array<string, mixed>>  $pages
     * @param  list<?list<mixed>>  $rows
     */
    private static function rejectEmptyPage(array $pages, array $rows): void
    {
        if (count($pages) === 1) {
            return;
        }

        foreach (array_keys($pages) as $index) {
            if ($rows[$index] === []) {
                throw new InvalidArgumentException(sprintf(
                    'stubPages() page %d carries no rows, in a sequence of %d. An empty page is only servable as the whole sequence: everywhere else all() reports a fault the endpoint never stated — PAGINATION_TRUNCATED where the page sits before the end, PAGINATION_INCONSISTENT where it sits at the end — and the pages behind it never reach the caller. Drop it and let the last page carrying rows end the sequence.',
                    $index + 1,
                    count($pages),
                ));
            }
        }
    }

    /**
     * Rejects a sequence whose own `totalCount` says the walk is already over
     * while a later page still waits to be served.
     *
     * `all()` stops as soon as the rows it has handed over reach the page's
     * `totalCount`, and reports `PAGINATION_INCONSISTENT` when that page also
     * says `hasMore` — which every non-final page does, since
     * {@see StubResponse::normalizePages()} defaults it there. A fixture like
     * `[['data' => [$a], 'totalCount' => 1], ['data' => [$b]]]` therefore yields
     * `$a` and then an error object mid-row-stream, and `$b` never reaches the
     * caller at all.
     *
     * That is the fake manufacturing the contradiction rather than the test
     * describing one, so it is refused here. A page that leaves `totalCount` out
     * is untouched: the inferred value describes the whole walk and cannot run
     * out early.
     *
     * @param  list<array<string, mixed>>  $pages
     * @param  list<?list<mixed>>  $rows
     * @param  list<?int>  $declaredCounts
     */
    private static function rejectExhaustedCount(array $pages, array $rows, array $declaredCounts): void
    {
        $lastIndex = count($pages) - 1;
        $delivered = 0;

        foreach (array_keys($pages) as $index) {
            $delivered += count($rows[$index] ?? []);

            $declared = $declaredCounts[$index];

            // A count of 0 is what an envelope omitting the key reports, so
            // `all()` cannot read it as "the set is complete" and it never ends
            // the walk early — a page declaring 0 is exempt for the same reason
            // one declaring nothing is. The last page ends the walk on its own,
            // so a count it has already delivered is the coherent case there.
            if ($index !== $lastIndex && $declared !== null && $declared > 0 && $delivered >= $declared) {
                throw new InvalidArgumentException(sprintf(
                    'stubPages() page %d declares totalCount %d, which the walk has already delivered by the end of that page, while %d more page(s) wait behind it. all() stops at the declared count and reports PAGINATION_INCONSISTENT there, so the later pages never reach the caller. Declare the count of the whole walk, or leave totalCount out and let it be inferred.',
                    $index + 1,
                    $declared,
                    $lastIndex - $index,
                ));
            }
        }
    }
}
