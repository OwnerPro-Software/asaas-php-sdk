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
     * An already-absolute pattern is rejected rather than prefixed a second
     * time. `Http::assertNotSent('https://host/path*')` is muscle memory from
     * the framework's own fake, and the doubled URL it would otherwise produce
     * (`…/v3/https://…/v3/payments**`) matches nothing. `assertSent` would at
     * least fail loudly, but `assertNotSent` — and `recorded()`, and therefore
     * `assertSent(..., times: 0)` — would pass unconditionally, turning "this
     * request must never be sent" into an assertion that cannot fail.
     */
    public static function absolute(string $baseUrl, string $pattern): string
    {
        if (str_starts_with($pattern, 'http://') || str_starts_with($pattern, 'https://')) {
            throw new InvalidArgumentException(sprintf(
                "Stub and assertion patterns must be relative to the Asaas base URL; got '%s'. Drop the scheme and host — write 'payments/pay_1' rather than '%s/payments/pay_1'.",
                $pattern,
                $baseUrl,
            ));
        }

        return sprintf('%s/%s', $baseUrl, ltrim($pattern, '/')).'*';
    }
}
