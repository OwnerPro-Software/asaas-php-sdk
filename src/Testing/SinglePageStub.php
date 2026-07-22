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
                    : self::terminalPage($body, $requested, $declared, $rowCount),
            );
        };
    }

    /**
     * The stub's own envelope is kept and only the walk-position keys are
     * replaced, so the terminal page answers with the same shape the stub
     * described — same rule `StubResponse::inferPagination()` states, and for
     * the same reason: a caller who put `object` or an extra top-level field on
     * the stub is describing the endpoint, and a page that quietly drops them
     * is a page their assertions cannot recognise.
     *
     * `totalCount` is one of the replaced keys rather than one of the kept
     * ones. A stub declaring `hasMore: true` with `totalCount: 5` and one row
     * describes a walk this fake serves exactly one row of, and a terminal page
     * repeating the 5 is the fake manufacturing the contradiction
     * `PAGINATION_SHORT` reports — the same thing
     * {@see PageSequenceGuard::rejectExhaustedCount()} refuses on a sequence.
     * The declared count still reaches `->list()`, which reads the stub's own
     * page; only the page that ends the walk is held to what was served.
     *
     * Holding the terminal page to what was served cuts both ways. A request
     * *below* the declared offset belongs to a walk that never reached the
     * stub's page — `all()` starts at offset 0 and only moves forward — so
     * that walk was served no rows at all, and repeating `$rowCount` there
     * manufactures the same `PAGINATION_SHORT` for rows the walk never saw.
     * It gets `totalCount: 0` instead, the value an envelope omitting the key
     * reports, and the walk ends empty and clean.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function terminalPage(array $body, int $offset, int $declared, int $rowCount): array
    {
        return array_merge(
            ['object' => 'list', 'limit' => $rowCount],
            $body,
            [
                'hasMore' => false,
                'offset' => $offset,
                'data' => [],
                'totalCount' => $offset < $declared ? 0 : $rowCount,
            ],
        );
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
