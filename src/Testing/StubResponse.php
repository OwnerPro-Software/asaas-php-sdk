<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;

/**
 * Builds stub responses through `Factory::response()` rather than the `Http`
 * facade: the fake is documented as usable outside Laravel, where no facade
 * root exists and every facade call raises `A facade root has not been set`.
 */
final class StubResponse
{
    /**
     * @param  array<string, mixed>|PromiseInterface|Closure  $stub
     */
    public static function normalize(array|PromiseInterface|Closure $stub): PromiseInterface|Closure
    {
        if ($stub instanceof PromiseInterface || $stub instanceof Closure) {
            return $stub;
        }

        PageEnvelopeGuard::validate($stub, 'stub()');

        // A lone stub that already declares any pagination key keeps its envelope:
        // with no sequence around it there is nothing the SDK knows better than
        // the caller. `normalizePages()` does know better — see its docblock. The
        // one thing a single response cannot honour is `hasMore: true`, which
        // promises a page it has no way to serve — see {@see SinglePageStub}.
        if (array_key_exists('hasMore', $stub) || array_key_exists('totalCount', $stub)) {
            return ($stub['hasMore'] ?? false) === true
                ? SinglePageStub::serve($stub, count(self::rowsOf($stub)))
                : Factory::response($stub);
        }

        // No `hasMore` reached the branch above, so the default and the verdict
        // are the same thing here: a lone stub is the whole result set.
        return Factory::response(
            self::withWalkPosition(
                self::inferPagination($stub, totalCount: count(self::rowsOf($stub))),
                hasMore: false,
            ),
        );
    }

    /**
     * Normalizes a whole `stubPages()` sequence at once.
     *
     * A page is only the end of the walk when it is the last one, so `hasMore`
     * cannot be inferred from a page in isolation — inferring `false` on every
     * page (as normalizing them one by one does) stops `all()` after the first.
     * Knowing the full sequence also lets `totalCount` describe the walk instead
     * of the single page.
     *
     * On the **last** page `hasMore` is overridden to `false` rather than
     * merely defaulted: a final page declaring `hasMore: true` promises a page
     * the sequence cannot serve, and the walk runs off the end into Laravel's
     * raw "response sequence is empty". No test means to assert that.
     *
     * On every **other** page it is only a default, so a page declaring
     * `hasMore: false` keeps it and ends the walk there. That is a legitimate
     * thing to pin — "the walk stops when the server says it does" is the
     * termination contract — and nothing else can express it. The cost is that
     * one realistic fixture pasted into several slots carries `hasMore: false`
     * everywhere and stops after the first; that is visible in the fixture,
     * whereas a suppressed early stop would not be recoverable at all.
     *
     * The parameter is any keyed array rather than a `list`, because that is
     * what a public seam is handed: `array_filter($fixtures, ...)` preserves
     * keys, and a signature promising otherwise only moves the problem to the
     * caller. Reindexing is the first thing this does.
     *
     * @param  array<array-key, array<string, mixed>>  $pages
     * @return list<PromiseInterface>
     */
    public static function normalizePages(array $pages): array
    {
        // The `list<>` on the parameter is PHPDoc, and `stubPages()` is a public
        // seam: `array_filter($fixtures, ...)` hands over pages keyed 0 and 2,
        // and the positional `$declaredCounts` below would then be read at keys
        // the sequence rules never wrote. Reindexing once here is what lets
        // every index in this method mean the same thing.
        $pages = array_values($pages);

        // Shape before sequence: a page declaring `totalCount` as a string is
        // not a contradiction the sequence rules can reason about, and reading
        // it as one would report the wrong defect.
        $declaredCounts = [];

        foreach ($pages as $index => $page) {
            $declaredCounts[] = PageEnvelopeGuard::validate($page, sprintf('stubPages() page %d', $index + 1));
        }

        // The rows themselves rather than their counts: `null` marks a body that
        // is not a page at all — an opaque stub sitting in a sequence — which
        // the guard has to tell apart from a page carrying no rows, and `[]`
        // counts the same as `null` everywhere except there.
        $rows = array_map(self::pageRows(...), $pages);

        PageSequenceGuard::validate($pages, $rows, $declaredCounts);

        $lastIndex = count($pages) - 1;
        $totalCount = self::servedRowCount($pages, $rows);

        $responses = [];

        foreach ($pages as $index => $page) {
            $body = self::inferPagination($page, totalCount: $totalCount);

            $responses[] = Factory::response(
                $index === $lastIndex
                    ? self::withWalkPosition($body, hasMore: false)
                    : self::withDefaultWalkPosition($body, hasMore: true),
            );
        }

        return $responses;
    }

    /**
     * Rows the walk will actually be handed: every page up to and including the
     * one that ends it.
     *
     * A page declaring `hasMore: false` keeps it — see this method's caller —
     * so the pages behind it are never requested. Counting them anyway would
     * put a `totalCount` on the served pages describing rows that never arrive,
     * which is the `PAGINATION_SHORT` fault: the fake would be manufacturing
     * the contradiction rather than the test describing one.
     *
     * @param  list<array<string, mixed>>  $pages
     * @param  list<?list<mixed>>  $rows  `null` where the body is not a page
     */
    private static function servedRowCount(array $pages, array $rows): int
    {
        $served = 0;

        foreach ($pages as $index => $page) {
            $served += count($rows[$index] ?? []);

            if (($page['hasMore'] ?? null) === false) {
                break;
            }
        }

        return $served;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<mixed>
     */
    private static function rowsOf(array $body): array
    {
        return self::pageRows($body) ?? [];
    }

    /**
     * Imposes the walk's verdict on `hasMore` over whatever the body declared.
     * Bodies that are not pages are left alone — a response with no `data`, or
     * whose `data` is an object, is opaque rather than a page of a walk.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function withWalkPosition(array $body, bool $hasMore): array
    {
        if (self::pageRows($body) === null) {
            return $body;
        }

        $body['hasMore'] = $hasMore;

        return $body;
    }

    /**
     * Fills in `hasMore` only where the body left it out, so a page that states
     * its own position keeps it.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function withDefaultWalkPosition(array $body, bool $hasMore): array
    {
        if (array_key_exists('hasMore', $body)) {
            return $body;
        }

        return self::withWalkPosition($body, $hasMore);
    }

    /**
     * The rows of a page, or `null` when the body is not a page at all —
     * distinct from `[]`, which is a genuinely empty page.
     *
     * @param  array<string, mixed>  $body
     * @return ?list<mixed>
     */
    private static function pageRows(array $body): ?array
    {
        if (! array_key_exists('data', $body) || ! is_array($body['data']) || ! array_is_list($body['data'])) {
            return null;
        }

        return $body['data'];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function inferPagination(array $body, int $totalCount): array
    {
        $rows = self::pageRows($body);

        if ($rows === null) {
            return $body;
        }

        // Defaults first so the stub wins on every key it declares: the caller's
        // envelope is preserved whole, extra fields included, and only the
        // pagination keys it left out are filled in. `hasMore` is absent from
        // the defaults on purpose — the walk-position helpers are the single
        // place that decides it, for both a lone stub and a sequence.
        return array_merge([
            'object' => 'list',
            'totalCount' => $totalCount,
            'limit' => count($rows),
            'offset' => 0,
        ], $body);
    }
}
