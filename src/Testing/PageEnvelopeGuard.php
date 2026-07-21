<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\PaginationEnvelope;

/**
 * Refuses a stubbed page envelope the SDK cannot read, at the point where it
 * was written rather than at request time.
 *
 * `AsaasPaginatedResult` types `totalCount` and `limit` as `int` and `hasMore`
 * as `bool`, and {@see PaginationEnvelope} refuses
 * anything else as an uninterpretable body. A fixture written as
 * `['data' => [$row], 'totalCount' => '1']` would otherwise register happily
 * and then raise `IndeterminateResultException` from inside the call under
 * test — a failure pointing at the SDK for a defect that is in the fixture.
 * Same property {@see PageSequenceGuard} provides, applied to one page's shape
 * rather than to a sequence.
 *
 * Validating and reading are the same pass: the declared `totalCount` comes
 * back typed, so nothing downstream has to re-inspect what was already proven.
 *
 * @internal Registration detail of {@see StubResponse}.
 */
final class PageEnvelopeGuard
{
    /**
     * `offset` is deliberately not checked: the SDK paginates by the offset it
     * asked for and never reads the echoed one, so a stub declaring it loosely
     * reaches no typed parameter — {@see SinglePageStub} takes it as numeric.
     *
     * @param  array<string, mixed>  $body
     * @param  string  $origin  how to name this body in the failure message
     * @return int the declared `totalCount`, or 0 when the page leaves it out —
     *             the same value an envelope omitting the key reports
     */
    public static function validate(array $body, string $origin): int
    {
        if (! self::isPageEnvelope($body)) {
            return 0;
        }

        self::requireBool($body, 'hasMore', $origin);
        self::requireInt($body, 'limit', $origin);

        return self::requireInt($body, 'totalCount', $origin);
    }

    /** @param array<string, mixed> $body */
    private static function requireInt(array $body, string $key, string $origin): int
    {
        $value = $body[$key] ?? 0;

        if (! is_int($value)) {
            throw self::mistyped($origin, $key, 'int', $value);
        }

        return $value;
    }

    /** @param array<string, mixed> $body */
    private static function requireBool(array $body, string $key, string $origin): bool
    {
        $value = $body[$key] ?? false;

        if (! is_bool($value)) {
            throw self::mistyped($origin, $key, 'bool', $value);
        }

        return $value;
    }

    private static function mistyped(string $origin, string $key, string $expected, mixed $value): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            '%s declares %s as %s, but the pagination envelope types it as %s. A 2xx carrying that shape is an unreadable body: the SDK raises IndeterminateResultException at request time instead of failing here. Declare it as %s, or leave the key out and let it be inferred.',
            $origin,
            $key,
            get_debug_type($value),
            $expected,
            $expected,
        ));
    }

    /**
     * Only bodies that are pages are held to the envelope's types. A stub of a
     * single resource is an opaque body the walk never reads — it may carry a
     * `data` object, or a `limit` meaning something in its own domain — and
     * rejecting those would refuse fixtures the SDK handles perfectly well.
     * The triggers are the ones {@see StubResponse::normalize()} already reads
     * as "this describes a page": a list-shaped `data`, or a declared walk
     * position.
     *
     * @param  array<string, mixed>  $body
     */
    private static function isPageEnvelope(array $body): bool
    {
        if (array_key_exists('hasMore', $body) || array_key_exists('totalCount', $body)) {
            return true;
        }

        return isset($body['data']) && is_array($body['data']) && array_is_list($body['data']);
    }
}
