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
     * @param  list<int>  $rowCounts  rows per page, positionally aligned with `$pages`
     */
    public static function validate(array $pages, array $rowCounts): void
    {
        self::rejectEmptySequence($pages);
        self::rejectExhaustedCount($pages, $rowCounts);
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
     * @param  list<int>  $rowCounts
     */
    private static function rejectExhaustedCount(array $pages, array $rowCounts): void
    {
        $lastIndex = count($pages) - 1;
        $delivered = 0;

        foreach ($pages as $index => $page) {
            $delivered += $rowCounts[$index];

            $declared = $page['totalCount'] ?? null;

            // A count of 0 is what an envelope omitting the key reports, so
            // `all()` cannot read it as "the set is complete" and it never ends
            // the walk early. The last page ends the walk on its own, so a count
            // it has already delivered is the coherent case there.
            if ($index !== $lastIndex && is_int($declared) && $declared > 0 && $delivered >= $declared) {
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
