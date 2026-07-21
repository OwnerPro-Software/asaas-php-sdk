<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;

/**
 * Serves a lone `hasMore: true` stub as the one page it describes, and the
 * empty terminal page for anything past it.
 *
 * A lone stub is a single response replayed for every matching request, so
 * declaring `hasMore: true` on one describes a walk whose next page is forever
 * identical to the current one: `->all()` would re-request the same rows and
 * never terminate. Answering requests past the stub's own page with the empty
 * page a real endpoint returns lets `->list()` still observe the declared
 * `hasMore` while the walk ends. `stubPages()` models an actual multi-page walk.
 *
 * @internal Response-shaping detail of {@see StubResponse}.
 */
final class SinglePageStub
{
    /**
     * "Its own page" is the offset the stub declares, not offset 0: a stub
     * written to model page two (`offset: 10`) must answer the request that
     * asks for offset 10, or the test sees an empty page it never described.
     * A request carrying no `offset` at all — a bare `->list()` — is asking for
     * whatever the stub is, so it gets the body too.
     *
     * The anti-loop guarantee is unaffected: `next()` always sends an explicit
     * `offset` of `offset + count($data)`, which differs from the declared one
     * by construction and therefore still lands on the terminal page.
     *
     * @param  array<string, mixed>  $body
     * @param  int  $rowCount  rows the stub carries, used to describe the
     *                         terminal page when the stub declares no
     *                         `totalCount`/`limit` of its own
     * @return Closure(Request): PromiseInterface
     */
    public static function serve(array $body, int $rowCount): Closure
    {
        return static function (Request $request) use ($body, $rowCount): PromiseInterface {
            $requested = self::requestedOffset($request);
            $declared = is_numeric($body['offset'] ?? null) ? (int) $body['offset'] : 0;

            return Factory::response(
                $requested === null || $requested === $declared
                    ? $body
                    : self::terminalPage($body, $requested, $rowCount),
            );
        };
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function terminalPage(array $body, int $offset, int $rowCount): array
    {
        return [
            'object' => 'list',
            'hasMore' => false,
            'totalCount' => $body['totalCount'] ?? $rowCount,
            'limit' => $body['limit'] ?? $rowCount,
            'offset' => $offset,
            'data' => [],
        ];
    }

    /**
     * Answers `null` when the request carries no usable `offset`, which is a
     * different question from "offset zero": a bare `->list()` is asking for
     * whatever page the stub represents, while `->list(['offset' => 0])` is
     * asking for the first one specifically.
     */
    private static function requestedOffset(Request $request): ?int
    {
        $query = parse_url($request->url(), PHP_URL_QUERY);

        if (! is_string($query)) {
            return null;
        }

        parse_str($query, $params);

        $offset = $params['offset'] ?? null;

        return is_numeric($offset) ? (int) $offset : null;
    }
}
