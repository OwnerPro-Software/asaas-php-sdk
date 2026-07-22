<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use InvalidArgumentException;

/**
 * Refuses a `stubPages()` page that repeats the rows of the one before it
 * while the injected `hasMore: true` still promises another.
 *
 * The walk reads that repeat as the endpoint ignoring the offset it was sent:
 * `all()` reports `PAGINATION_STALLED` there and strands the pages behind it —
 * a fault the endpoint never committed, manufactured by the default
 * {@see StubResponse::normalizePages()} injects. The natural way to write the
 * shape is pasting one realistic fixture into several slots, and like the
 * shapes {@see PageSequenceGuard} refuses, it is rejected where it was written
 * rather than at request time.
 *
 * Only the injected default is refused. A repeat declaring `hasMore: false`
 * ends the walk instead of stalling it, one declaring `hasMore: true` is the
 * author asking for the stall — the one declarative way to exercise
 * `PAGINATION_STALLED` handling through this fake — and the final page is
 * forced to `hasMore: false`, where a repeat is just the last page. Bodies
 * that are not pages (`null` rows) are opaque to the walk and exempt.
 *
 * @internal Registration detail of {@see StubResponse}.
 */
final class RepeatedPageGuard
{
    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  list<?list<mixed>>  $rows  each page's rows, positionally aligned
     *                                    with `$pages`, and `null` where the
     *                                    body is not a page at all
     */
    public static function validate(array $pages, array $rows): void
    {
        $lastIndex = count($pages) - 1;

        foreach (array_keys($pages) as $index) {
            if (self::repeatsIntoInjectedHasMore($pages, $rows, $index, $lastIndex)) {
                throw new InvalidArgumentException(sprintf(
                    'stubPages() page %d repeats the rows of page %d and is served with hasMore: true, so all() reads it as the endpoint ignoring the offset it was sent and reports PAGINATION_STALLED — a fault the endpoint never committed — while %d page(s) behind it never reach the caller. Vary the fixture rows, declare hasMore: false to end the walk there, or declare hasMore: true on the page to simulate a stalled endpoint on purpose.',
                    $index + 1,
                    $index,
                    $lastIndex - $index,
                ));
            }

            // Pages behind a declared stop are never requested, so a repeat
            // there cannot stall a walk that already ended — the same early
            // stop as {@see StubResponse::servedRowCount()}.
            if (($pages[$index]['hasMore'] ?? null) === false) {
                return;
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  list<?list<mixed>>  $rows
     */
    private static function repeatsIntoInjectedHasMore(array $pages, array $rows, int $index, int $lastIndex): bool
    {
        return $index > 0
            && $index !== $lastIndex
            && $rows[$index] !== null
            && $rows[$index] === $rows[$index - 1]
            && ! array_key_exists('hasMore', $pages[$index]);
    }
}
