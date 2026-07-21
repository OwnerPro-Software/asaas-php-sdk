<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use InvalidArgumentException;

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

        // A lone stub that already declares any pagination key keeps its envelope:
        // with no sequence around it there is nothing the SDK knows better than
        // the caller. `normalizePages()` does know better — see its docblock. The
        // one thing a single response cannot honour is `hasMore: true`, which
        // promises a page it has no way to serve — see `pageOneOnly()`.
        if (array_key_exists('hasMore', $stub) || array_key_exists('totalCount', $stub)) {
            return ($stub['hasMore'] ?? false) === true
                ? self::pageOneOnly($stub)
                : Factory::response($stub);
        }

        return Factory::response(self::inferPagination($stub, hasMore: false, totalCount: count(self::rowsOf($stub))));
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
     * @param  list<array<string, mixed>>  $pages
     * @return list<PromiseInterface|Closure>
     */
    public static function normalizePages(array $pages): array
    {
        if ($pages === []) {
            throw new InvalidArgumentException(
                'stubPages() requires at least one page; an empty sequence has no response to serve and would surface as an exhausted-sequence error at request time instead of here.',
            );
        }

        $totalCount = array_sum(array_map(static fn (array $page): int => count(self::rowsOf($page)), $pages));
        $lastIndex = count($pages) - 1;

        $responses = [];

        foreach ($pages as $index => $page) {
            $responses[] = Factory::response(
                self::inferPagination($page, hasMore: $index < $lastIndex, totalCount: $totalCount),
            );
        }

        return $responses;
    }

    /**
     * Serves a `hasMore: true` stub as page one and nothing beyond it.
     *
     * A lone stub is a single response, replayed for every matching request.
     * Declaring `hasMore: true` on one therefore describes a walk whose next
     * page is forever identical to the current one: `->all()` re-requests the
     * same rows and never terminates. Requests past page one get the empty
     * terminal page a real endpoint answers with, so `->list()` still observes
     * the declared `hasMore` while the walk ends. Use `stubPages()` to model an
     * actual multi-page walk.
     *
     * @param  array<string, mixed>  $body
     * @return Closure(Request): PromiseInterface
     */
    private static function pageOneOnly(array $body): Closure
    {
        return static function (Request $request) use ($body): PromiseInterface {
            $offset = self::requestedOffset($request);

            return Factory::response($offset === 0 ? $body : self::terminalPage($body, $offset));
        };
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function terminalPage(array $body, int $offset): array
    {
        return [
            'object' => 'list',
            'hasMore' => false,
            'totalCount' => $body['totalCount'] ?? count(self::rowsOf($body)),
            'limit' => $body['limit'] ?? count(self::rowsOf($body)),
            'offset' => $offset,
            'data' => [],
        ];
    }

    private static function requestedOffset(Request $request): int
    {
        $query = parse_url($request->url(), PHP_URL_QUERY);

        if (! is_string($query)) {
            return 0;
        }

        parse_str($query, $params);

        $offset = $params['offset'] ?? null;

        return is_numeric($offset) ? (int) $offset : 0;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<mixed>
     */
    private static function rowsOf(array $body): array
    {
        if (! isset($body['data']) || ! is_array($body['data']) || ! array_is_list($body['data'])) {
            return [];
        }

        return $body['data'];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function inferPagination(array $body, bool $hasMore, int $totalCount): array
    {
        if (! array_key_exists('data', $body)) {
            return $body;
        }

        if (! is_array($body['data']) || ! array_is_list($body['data'])) {
            return $body;
        }

        // Defaults first so the stub wins on every key it declares: the caller's
        // envelope is preserved whole, extra fields included, and only the
        // pagination keys it left out are filled in.
        return array_merge([
            'object' => 'list',
            'hasMore' => $hasMore,
            'totalCount' => $totalCount,
            'limit' => count($body['data']),
            'offset' => 0,
        ], $body);
    }
}
