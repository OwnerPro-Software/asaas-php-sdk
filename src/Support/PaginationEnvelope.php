<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Reads the pagination envelope of a 2xx list response, or refuses it.
 *
 * `paginate()` used to hand `$body['data']`, `$body['totalCount']`,
 * `$body['hasMore']` and `$body['limit']` straight to the typed parameters of
 * {@see AsaasPaginatedResult::success()}. Under `strict_types` a proxy, WAF or
 * gateway answering 200 with `{"data": "oops"}` — or with `totalCount` as a
 * numeric string — raised a `TypeError` out of the middle of the SDK, escaping
 * the Result contract entirely. {@see ErrorEnvelope} already guards the error
 * side of the same problem; this is its counterpart for the list side.
 *
 * A malformed field is reported as {@see IndeterminateResultException} with
 * `phase: 'body'` — the phase that already means "a 2xx arrived whose body the
 * SDK cannot interpret". Nothing is coerced: `totalCount` bounds the walk and
 * `hasMore` continues it, so silently reading `"3"` as `3` or `"false"` as
 * `true` would end a walk early or run one forever while looking like a
 * complete answer. On endpoints that move money a truncated list read as
 * complete is the expensive failure, so an unreadable envelope says so.
 *
 * @internal Interpretation detail of {@see PaginatesResults} — consume pages
 * via `AsaasPaginatedResult`.
 */
final readonly class PaginationEnvelope
{
    /** @param list<array<string, mixed>> $data */
    private function __construct(
        public array $data,
        public int $totalCount,
        public bool $hasMore,
        public int $limit,
    ) {}

    /** @param array<string, mixed> $body */
    public static function from(array $body, RawResponse $rawResponse): self
    {
        return new self(
            data: self::rows($body, $rawResponse),
            totalCount: self::integer($body, 'totalCount', $rawResponse),
            hasMore: self::boolean($body, 'hasMore', $rawResponse),
            limit: self::integer($body, 'limit', $rawResponse),
        );
    }

    /**
     * An absent `data` is an empty page rather than a malformed one: the
     * envelope of an endpoint with nothing to return is the one shape the SDK
     * can read without it.
     *
     * Rows are held to the same rule {@see ErrorEnvelope} applies to error
     * rows: a row has to be a JSON *object*, since that is what callers index
     * by field name. `{"data": [[1, 2]]}` survives an `is_array()` check and
     * would then be handed over as if its int keys were fields. The empty
     * array is allowed through for the same reason it is there — `{}` and `[]`
     * decode alike, and a row carrying no fields is still a row.
     *
     * @param  array<string, mixed>  $body
     * @return list<array<string, mixed>>
     */
    private static function rows(array $body, RawResponse $rawResponse): array
    {
        $data = $body['data'] ?? [];

        if (! is_array($data) || ! array_is_list($data)) {
            throw new IndeterminateResultException('body', response: $rawResponse);
        }

        foreach ($data as $row) {
            if (! is_array($row) || ($row !== [] && array_is_list($row))) {
                throw new IndeterminateResultException('body', response: $rawResponse);
            }
        }

        /** @var list<array<string, mixed>> $data */
        return $data;
    }

    /**
     * An absent count or limit reads as 0, which the walk already treats as
     * "the envelope did not say" — see
     * {@see PaginatesResults::hasDeliveredWholeSet()}.
     *
     * @param  array<string, mixed>  $body
     */
    private static function integer(array $body, string $key, RawResponse $rawResponse): int
    {
        $value = $body[$key] ?? 0;

        if (! is_int($value)) {
            throw new IndeterminateResultException('body', response: $rawResponse);
        }

        return $value;
    }

    /** @param array<string, mixed> $body */
    private static function boolean(array $body, string $key, RawResponse $rawResponse): bool
    {
        $value = $body[$key] ?? false;

        if (! is_bool($value)) {
            throw new IndeterminateResultException('body', response: $rawResponse);
        }

        return $value;
    }
}
