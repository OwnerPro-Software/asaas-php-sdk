<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use PHPUnit\Framework\AssertionFailedError;

mutates(FakeAsaasClient::class);

it('recorded() returns every (Request, Response) pair', function (): void {
    $fake = AsaasClient::fake([
        'payments/*' => ['id' => 'pay_1'],
        'webhooks' => ['id' => 'wh_1'],
    ]);

    $fake->payments()->find('pay_1');
    $fake->webhooks()->list();

    $recorded = $fake->recorded();

    expect($recorded)->toHaveCount(2);
    expect($recorded[0][0])->toBeInstanceOf(Request::class);
    expect($recorded[0][1])->toBeInstanceOf(Response::class);
});

it('recorded(pattern) filters to matches only', function (): void {
    $fake = AsaasClient::fake([
        'payments/*' => ['id' => 'pay_1'],
        'webhooks' => ['id' => 'wh_1'],
    ]);

    $fake->payments()->find('pay_1');
    $fake->webhooks()->list();

    expect($fake->recorded('payments/*'))->toHaveCount(1);
    expect($fake->recorded('webhooks'))->toHaveCount(1);
    expect($fake->recorded('pix/*'))->toHaveCount(0);
});

it('assertSent matches by pattern + callback', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    $fake->payments()->create([
        'value' => 100,
        'customer' => 'c_1',
        'billingType' => 'PIX',
        'dueDate' => '2026-01-01',
    ]);

    $fake->assertSent('payments', fn (Request $r) => $r['value'] === 100.0);
});

it('assertSent matches by callback only', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    $fake->payments()->create([
        'value' => 100,
        'customer' => 'c_1',
        'billingType' => 'PIX',
        'dueDate' => '2026-01-01',
    ]);

    $fake->assertSent(fn (Request $r) => str_ends_with($r->url(), '/payments'));
});

it('assertSent supports times: parameter', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');
    $fake->payments()->find('pay_2');

    $fake->assertSent('payments/*', times: 2);
});

it('assertSent throws when no match', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    expect(fn () => $fake->assertSent('payments'))
        ->toThrow(AssertionFailedError::class);
});

it('assertNotSent passes when pattern was not requested', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    $fake->assertNotSent('webhooks/*');
});

it('assertNotSent throws when pattern was requested', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertNotSent('payments/*'))
        ->toThrow(AssertionFailedError::class);
});

it('assertSentCount reflects total recorded requests', function (): void {
    $fake = AsaasClient::fake([
        'payments/*' => ['id' => 'pay_1'],
        'webhooks' => ['id' => 'wh_1'],
    ]);

    $fake->payments()->find('pay_1');
    $fake->webhooks()->list();

    $fake->assertSentCount(2);
});

it('assertNothingSent passes for unused fake', function (): void {
    AsaasClient::fake()->assertNothingSent();
});

it('assertNothingSent throws when any request was made', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertNothingSent())
        ->toThrow(AssertionFailedError::class);
});

it('assertSentInOrder accepts string patterns matching in sequence', function (): void {
    $fake = AsaasClient::fake([
        'accounts' => ['id' => 'acc_1'],
        'accounts/*' => ['id' => 'acc_1'],
        'accounts/*/accessTokens' => ['id' => 'tok_1'],
    ]);

    $fake->accounts()->create([
        'name' => 'A',
        'email' => 'a@b.c',
        'cpfCnpj' => '00000000000',
        'mobilePhone' => '11999999999',
        'incomeValue' => 1000.0,
        'address' => 'Rua A',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '00000000',
    ]);
    $fake->accounts()->find('acc_1');
    $fake->accounts()->createAccessToken('acc_1');

    $fake->assertSentInOrder([
        'accounts',
        'accounts/*',
        'accounts/*/accessTokens',
    ]);
});

it('assertSentInOrder accepts callable matchers', function (): void {
    $fake = AsaasClient::fake([
        'payments' => ['id' => 'pay_1'],
        'payments/*' => ['id' => 'pay_1'],
    ]);

    $fake->payments()->create([
        'value' => 100,
        'customer' => 'c',
        'billingType' => 'PIX',
        'dueDate' => '2026-01-01',
    ]);
    $fake->payments()->find('pay_1');

    $fake->assertSentInOrder([
        fn (Request $r) => $r->method() === 'POST',
        fn (Request $r) => $r->method() === 'GET',
    ]);
});

it('assertSentInOrder fails when matchers occur out of order', function (): void {
    $fake = AsaasClient::fake([
        'payments/*' => ['id' => 'pay_1'],
        'webhooks' => ['id' => 'wh_1'],
    ]);

    $fake->webhooks()->list();
    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertSentInOrder(['payments/*', 'webhooks']))
        ->toThrow(AssertionFailedError::class);
});

it('assertSentInOrder allows interleaved unrelated requests', function (): void {
    $fake = AsaasClient::fake([
        'accounts' => ['id' => 'acc_1'],
        'pix/qrCodes/static' => ['id' => 'qr_1'],
        'accounts/*' => ['id' => 'acc_1'],
    ]);

    $fake->accounts()->create([
        'name' => 'A',
        'email' => 'a@b.c',
        'cpfCnpj' => '00000000000',
        'mobilePhone' => '11999999999',
        'incomeValue' => 1000.0,
        'address' => 'Rua A',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '00000000',
    ]);
    $fake->pix()->createStaticQrCode(['addressKey' => 'k']);
    $fake->accounts()->find('acc_1');

    $fake->assertSentInOrder(['accounts', 'accounts/*']);
});

it('assertSent times: 0 fails when at least one matching request was sent', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertSent('payments/*', times: 0))
        ->toThrow(AssertionFailedError::class);
});

it('assertSent times: passes for exact match and short-circuits without checking minimum', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    // Zero matches with times:0 must pass — proves early-return path runs and
    // the second assertion (assertGreaterThan 0) is *not* invoked.
    $fake->assertSent('payments/*', times: 0);

    // Verify fluent chain return value.
    expect($fake->assertSent('payments/*', times: 0))->toBe($fake);
});

it('assertSent error message uses callback placeholder when no pattern given', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    try {
        $fake->assertSent(static fn (Request $r): bool => $r->method() === 'PURGE');
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())->toContain('<callback>');

        return;
    }

    throw new RuntimeException('Expected AssertionFailedError');
});

it('assertSent times: error message uses callback placeholder when no pattern given', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    try {
        $fake->assertSent(static fn (Request $r): bool => false, times: 1);
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())->toContain('<callback>');

        return;
    }

    throw new RuntimeException('Expected AssertionFailedError');
});

it('assertNotSent error message uses callback placeholder when no pattern given', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    try {
        $fake->assertNotSent(static fn (Request $r): bool => true);
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())->toContain('<callback>');

        return;
    }

    throw new RuntimeException('Expected AssertionFailedError');
});

it('assertSent error message includes the pattern string when pattern is given', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    try {
        $fake->assertSent('webhooks/specific-pattern');
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())->toContain('webhooks/specific-pattern');
        expect($e->getMessage())->not->toContain('<callback>');

        return;
    }

    throw new RuntimeException('Expected AssertionFailedError');
});

it('assertSent times: error message includes the pattern when pattern is given', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    try {
        $fake->assertSent('payments/specific-x', times: 5);
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())->toContain('payments/specific-x');
        expect($e->getMessage())->not->toContain('<callback>');

        return;
    }

    throw new RuntimeException('Expected AssertionFailedError');
});

it('assertNotSent error message includes the pattern when pattern is given', function (): void {
    $fake = AsaasClient::fake(['payments/specific-y' => ['id' => 'pay_y']]);

    $fake->payments()->find('specific-y');

    try {
        $fake->assertNotSent('payments/specific-y');
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())->toContain('payments/specific-y');
        expect($e->getMessage())->not->toContain('<callback>');

        return;
    }

    throw new RuntimeException('Expected AssertionFailedError');
});

it('assertSent fluent chain returns the same fake instance', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    expect($fake->assertSent('payments/*'))->toBe($fake);
    expect($fake->assertSent('payments/*', times: 1))->toBe($fake);
    expect($fake->assertNotSent('webhooks'))->toBe($fake);
});

it('assertSentInOrder advances the cursor strictly past each match', function (): void {
    // Two POSTs to the same pattern; matchers must be assigned to *distinct*
    // entries, not both to the first one. If cursor used $j (not $j+1) after a
    // match, both matchers would resolve to entry 0 and pass falsely; this
    // test forces the second matcher to require the *second* request.
    $fake = AsaasClient::fake([
        'payments' => Http::sequence()
            ->push(['id' => 'pay_first'])
            ->push(['id' => 'pay_second']),
    ]);

    $fake->payments()->create([
        'value' => 1, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);
    $fake->payments()->create([
        'value' => 2, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // Same pattern twice — must advance past the first match to find the second.
    $fake->assertSentInOrder(['payments', 'payments']);

    // Three matchers on two requests must fail (cursor advanced past both).
    expect(fn () => $fake->assertSentInOrder(['payments', 'payments', 'payments']))
        ->toThrow(AssertionFailedError::class);
});

it('matchRecorded callback receives the Request as the first argument', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    $fake->payments()->create([
        'value' => 100, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // Callback must receive the Request instance — if entry[0] were swapped
    // for entry[1] (Response), the URL/method checks below would fail.
    $fake->assertSent(
        'payments',
        static fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/payments'),
        times: 1,
    );
});

it('assertSent callback receives the Response as the second argument', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_response_value']]);

    $fake->payments()->create([
        'value' => 1, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // The second argument must be the Response — pins the DecrementInteger
    // mutation that would substitute entry[0] (Request) for both args.
    $fake->assertSent(
        'payments',
        static fn (Request $request, Response $response): bool => $response->json('id') === 'pay_response_value',
        times: 1,
    );
});

it('entryMatches closure callback path receives the Request not the Response', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    $fake->payments()->create([
        'value' => 100, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // Closure-only matcher in assertSentInOrder must use entry[0] (Request);
    // method() does not exist on Response, so a swap would error.
    $fake->assertSentInOrder([
        static fn (Request $request): bool => $request->method() === 'POST',
    ]);
});

it('entryMatches closure receives the Response as the second argument', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_response_arg']]);

    $fake->payments()->create([
        'value' => 1, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // Asserts that entry[1] (Response) is passed correctly — pins the
    // DecrementInteger mutation that would substitute entry[0] for both args.
    $fake->assertSentInOrder([
        static fn (Request $request, Response $response): bool => $response->json('id') === 'pay_response_arg',
    ]);
});

it('entryMatches closure that returns truthy non-bool still matches (bool cast)', function (): void {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_1']]);

    $fake->payments()->create([
        'value' => 1, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // Returns a truthy non-bool (string) — pins the (bool) cast in entryMatches.
    /** @phpstan-ignore-next-line */
    $fake->assertSentInOrder([static fn (Request $r) => $r->method()]);
});

it('matchRecorded returns boolean-true predicate matches even when callback returns truthy non-bool', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);

    $fake->payments()->find('pay_1');

    // Non-strict-boolean truthy return value still keeps the entry —
    // pins the (bool) cast in matchRecorded.
    /** @phpstan-ignore-next-line */
    $fake->assertSent('payments/*', static fn (Request $r) => $r->url() ?: 0, times: 1);
});

it('recorded(pattern) returns sequentially indexed list when filtering', function (): void {
    $fake = AsaasClient::fake([
        'payments' => ['id' => 'pay_post'],
        'payments/*' => ['id' => 'pay_get'],
        'webhooks' => ['id' => 'wh_1'],
    ]);

    $fake->webhooks()->list();
    $fake->payments()->create([
        'value' => 1, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);
    $fake->webhooks()->list();

    $filtered = $fake->recorded('payments');

    // array_values invariant: numeric, sequential keys starting from 0,
    // not preserved keys from the original (which would be [1 => ...]).
    expect(array_keys($filtered))->toBe([0]);
});

it('stubPages with already-trailing-* pattern behaves the same as without it', function (): void {
    $fake = AsaasClient::fake()->stubPages('payments*', [
        ['data' => [['id' => 'a']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0],
    ]);

    $items = iterator_to_array($fake->payments()->all(['limit' => 10]), preserve_keys: false);

    expect($items)->toBe([['id' => 'a']]);
});

it('assertSentInOrder rejects an empty matcher list', function (): void {
    expect(fn () => AsaasClient::fake()->assertSentInOrder([]))
        ->toThrow(AssertionFailedError::class, 'assertSentInOrder requires at least one matcher');
});

it('rejects assertSent() with two closures instead of dropping the second', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    // Honouring only the first predicate turns this into an assertion that can
    // never fail: the second closure is impossible, yet the call would pass.
    expect(fn () => $fake->assertSent(
        fn (Request $request): bool => true,
        fn (Request $request): bool => false,
    ))->toThrow(InvalidArgumentException::class);
});

it('rejects assertNotSent() with two closures', function (): void {
    $fake = AsaasClient::fake();

    expect(fn () => $fake->assertNotSent(
        fn (Request $request): bool => true,
        fn (Request $request): bool => true,
    ))->toThrow(InvalidArgumentException::class);
});

it('still accepts a lone closure predicate', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    $fake->assertSent(fn (Request $request): bool => $request->method() === 'GET');

    expect(fn () => $fake->assertSent(fn (Request $request): bool => $request->method() === 'POST'))
        ->toThrow(AssertionFailedError::class);
});

it('rejects an absolute pattern instead of letting assertNotSent() pass unconditionally', function (string $method): void {
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect(fn () => $fake->{$method}('https://api-sandbox.asaas.com/v3/payments*'))
        ->toThrow(InvalidArgumentException::class, 'must be relative to the Asaas base URL');
})->with(['assertSent', 'assertNotSent', 'recorded']);

it('rejects a host-carrying pattern no matter how it is dressed up', function (string $pattern): void {
    // Each of these names a host of its own and would be concatenated onto the
    // base URL, building a doubled address that matches nothing — so
    // assertNotSent() could not fail. The leading-slash form is the one that
    // used to slip through: the guard read the raw pattern while the version
    // guard beside it read the slash-stripped one.
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertNotSent($pattern))
        ->toThrow(InvalidArgumentException::class, 'must be relative to the Asaas base URL');
})->with([
    '/https://api-sandbox.asaas.com/v3/payments*',
    '///https://api-sandbox.asaas.com/v3/payments*',
    'HTTPS://api-sandbox.asaas.com/v3/payments*',
    'ftp://api-sandbox.asaas.com/v3/payments*',
    '//api-sandbox.asaas.com/v3/payments*',
]);

it('still resolves a relative pattern whose first segment merely looks host-like', function (): void {
    // The guard keys on the `://` marker, not on dots in a segment: a path is
    // not a host just because it carries one.
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect($fake->recorded('api.asaas.com/payments'))->toBe([]);
});

it('rejects an absolute stub pattern where it was written', function (): void {
    expect(fn () => AsaasClient::fake(['http://api-sandbox.asaas.com/v3/payments' => ['id' => 'pay_1']]))
        ->toThrow(InvalidArgumentException::class, 'must be relative to the Asaas base URL');
});

it('rejects an invalid stub pattern even when an earlier stub would match first', function (): void {
    // The router reaches a pattern only if nothing before it matched, so a bad
    // pattern behind a catch-all used never to be validated at all: the caller
    // wrote a specific stub, silently got the generic one, and saw no error.
    expect(fn () => AsaasClient::fake([
        'payments' => ['id' => 'catch_all'],
        'https://api-sandbox.asaas.com/v3/payments/pay_1' => ['id' => 'specific'],
    ]))->toThrow(InvalidArgumentException::class, 'must be relative to the Asaas base URL');
});

it('rejects an invalid pattern passed to stub() after construction', function (): void {
    $fake = AsaasClient::fake();

    expect(fn () => $fake->stub('v3/payments', ['id' => 'pay_1']))
        ->toThrow(InvalidArgumentException::class, "already ends in '/v3'");
});

it('rejects a v3-prefixed pattern instead of letting assertNotSent() pass unconditionally', function (string $method): void {
    // docs.asaas.com writes every endpoint as `/v3/payments/{id}`, so this is the
    // shape muscle memory produces. The base URL already ends in `/v3`, and the
    // doubled `…/v3/v3/payments*` it would build matches nothing — so the
    // request-was-never-sent assertion could not fail.
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect(fn () => $fake->{$method}('v3/payments/pay_1'))
        ->toThrow(InvalidArgumentException::class, "already ends in '/v3'");
})->with(['assertSent', 'assertNotSent', 'recorded']);

it('rejects a bare v3 pattern and a leading-slash v3 pattern alike', function (string $pattern): void {
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertNotSent($pattern))
        ->toThrow(InvalidArgumentException::class, "already ends in '/v3'");
})->with(['v3', 'v3/', '/v3/payments']);

it('does not mistake a path merely starting with v3 for the version segment', function (): void {
    // The guard keys on the `v3/` segment, not on the two characters: a real
    // endpoint named `v3things` must still resolve normally.
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect($fake->recorded('v3things'))->toBe([]);
});

it('rejects a v3-prefixed stub pattern where it was written', function (): void {
    expect(fn () => AsaasClient::fake(['v3/payments' => ['id' => 'pay_1']]))
        ->toThrow(InvalidArgumentException::class, "already ends in '/v3'");
});

it('rejects a host-carrying pattern padded with whitespace', function (string $pattern): void {
    // Both guards anchor at `^`, so a single leading space used to walk a
    // scheme — or a `v3/` — straight past them into the doubled URL they exist
    // to reject, leaving assertNotSent unable to fail.
    $fake = AsaasClient::fake(['*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');

    expect(fn () => $fake->assertNotSent($pattern))->toThrow(InvalidArgumentException::class);
})->with([
    ' https://api-sandbox.asaas.com/v3/payments*',
    "\thttps://api-sandbox.asaas.com/v3/payments*",
    '  //api-sandbox.asaas.com/v3/payments*',
    ' v3/payments',
    "v3/payments\n",
]);
