<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use InvalidArgumentException;

/**
 * Turns a caller-supplied stub/assertion pattern into the absolute glob that
 * {@see FakeAsaasClient} matches recorded request URLs against.
 *
 * @internal Matching detail of {@see FakeAsaasClient}.
 */
final class StubPattern
{
    /**
     * Joins baseUrl + pattern and unconditionally appends `*`. This makes
     * path-only patterns ('payments') match the same URLs they would if the
     * user typed 'payments*' — including those carrying query strings or
     * trailing segments — so users don't have to remember to wildcard every
     * GET endpoint. Patterns that already end in `*` get a redundant trailing
     * `*` (`payments/**`); `Str::is` treats `**` and `*` identically so
     * matching behaviour stays the same.
     *
     * A pattern that already carries scheme+host, or that repeats the `v3/`
     * version segment the base URL ends with, is rejected rather than prefixed
     * a second time. Both forms are muscle memory — the first from
     * `Http::assertNotSent('https://host/path*')`, the second from the way
     * docs.asaas.com writes every endpoint — and the doubled URL they produce
     * (`…/v3/https://…/v3/payments**`, `…/v3/v3/payments*`) matches nothing.
     * `assertSent` would at least fail loudly, but `assertNotSent` — and
     * `recorded()`, and therefore `assertSent(..., times: 0)` — would pass
     * unconditionally, turning "this request must never be sent" into an
     * assertion that cannot fail.
     */
    public static function absolute(string $baseUrl, string $pattern): string
    {
        // Whitespace is stripped before anything else: both guards below anchor
        // at `^`, so a single leading space walked a scheme — or a `v3/` — past
        // them and into the doubled URL they exist to reject.
        $relative = ltrim(trim($pattern), '/');

        if (self::carriesHost($pattern, $relative)) {
            throw new InvalidArgumentException(sprintf(
                "Stub and assertion patterns must be relative to the Asaas base URL; got '%s'. Drop the scheme and host — write 'payments/pay_1' rather than '%s/payments/pay_1'.",
                $pattern,
                $baseUrl,
            ));
        }

        if ($relative === 'v3' || str_starts_with($relative, 'v3/')) {
            throw new InvalidArgumentException(sprintf(
                "Stub and assertion patterns must be relative to the Asaas base URL, which already ends in '/v3'; got '%s'. Drop the version segment — write '%s' rather than '%s'.",
                $pattern,
                substr($relative, 3) === '' ? '*' : ltrim(substr($relative, 3), '/'),
                $pattern,
            ));
        }

        return sprintf('%s/%s', $baseUrl, $relative).'*';
    }

    /**
     * Answers whether the pattern names a host of its own.
     *
     * The scheme is looked for on the **leading-slash-stripped** pattern, the
     * same string the version guard below reads and the same one that ends up
     * concatenated onto the base URL. Reading the raw pattern instead left
     * `/https://host/payments*` — one stray slash, a form `recorded()` accepts
     * everywhere else — walking straight past the guard and into the doubled
     * URL it exists to reject, which matches nothing and turns `assertNotSent`
     * into an assertion that cannot fail.
     *
     * Any scheme is rejected, not just `http`/`https`, and the match is
     * case-insensitive: the point is that the pattern carries a host, and
     * `HTTPS://` and `ftp://` carry one just as `https://` does.
     *
     * The protocol-relative form `//host/path` names a host without a scheme,
     * so it is read off the raw pattern — `ltrim()` has already eaten the
     * marker by the time `$relative` exists.
     */
    private static function carriesHost(string $pattern, string $relative): bool
    {
        return preg_match('#^[a-z][a-z0-9+.-]*://#i', $relative) === 1
            || str_starts_with(trim($pattern), '//');
    }
}
