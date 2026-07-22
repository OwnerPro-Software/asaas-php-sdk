<?php

declare(strict_types=1);

use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use OwnerPro\Asaas\Testing\NoMatchingStubException;

mutates(FakeAsaasClient::class);

it('infers single-page pagination from data only', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1'], ['id' => 'pay_2']],
    ]);

    $result = $fake->payments()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([['id' => 'pay_1'], ['id' => 'pay_2']]);
    expect($result->hasMore)->toBeFalse();
    expect($result->totalCount)->toBe(2);
});

it('respects explicit hasMore=true and totalCount', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1']],
        'hasMore' => true,
        'totalCount' => 42,
        'limit' => 10,
        'offset' => 0,
    ]);

    $result = $fake->payments()->list();

    expect($result->hasMore)->toBeTrue();
    expect($result->totalCount)->toBe(42);
    expect($result->limit)->toBe(10);
    expect($result->offset)->toBe(0);
});

it('stubPages() drives sequential ->all() iteration', function (): void {
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']], 'hasMore' => true,  'totalCount' => 2, 'limit' => 1, 'offset' => 0],
        ['data' => [['id' => 'b']], 'hasMore' => false, 'totalCount' => 2, 'limit' => 1, 'offset' => 1],
    ]);

    $items = iterator_to_array($fake->payments()->all(['limit' => 1]), preserve_keys: false);

    expect($items)->toBe([['id' => 'a'], ['id' => 'b']]);
});

it('stubPages() infers the pagination envelope like stub() does', function (): void {
    $fake = AsaasClient::fake()->stubPages('webhooks', [
        ['data' => [['id' => 'a'], ['id' => 'b']]],
    ]);

    $result = $fake->webhooks()->list();

    expect($result->totalCount)->toBe(2);
    expect($result->limit)->toBe(2);
    expect($result->hasMore)->toBeFalse();
});

it('stubPages() overrides hasMore on the last page, which cannot promise another', function (): void {
    // A final page declaring `hasMore: true` would send the walk past the end of
    // the sequence and surface as Laravel's raw "response sequence is empty" —
    // an error no test means to assert. Every other declared key is kept.
    $fake = AsaasClient::fake()->stubPages('webhooks', [
        ['data' => [['id' => 'a']], 'hasMore' => true, 'totalCount' => 9, 'limit' => 3],
    ]);

    $result = $fake->webhooks()->list();

    expect($result->totalCount)->toBe(9);
    expect($result->limit)->toBe(3);
    expect($result->hasMore)->toBeFalse();
});

it('stubPages() lets a non-final page declare hasMore:false and end the walk there', function (): void {
    // "The walk stops when the server says it does" is the termination contract,
    // and a sequence is the only way to pin it: page two exists but is never
    // requested. Overriding hasMore here would make that untestable.
    $fake = AsaasClient::fake()->stubPages('webhooks', [
        ['data' => [['id' => 'a']], 'hasMore' => false],
        ['data' => [['id' => 'b']]],
    ]);

    expect(iterator_to_array($fake->webhooks()->all()))->toBe([['id' => 'a']]);
});

it('stubPages() still fills in hasMore on a non-final page that declares none', function (): void {
    $fake = AsaasClient::fake()->stubPages('webhooks', [
        ['data' => [['id' => 'a']]],
        ['data' => [['id' => 'b']], 'hasMore' => false],
    ]);

    expect(iterator_to_array($fake->webhooks()->all()))
        ->toBe([['id' => 'a'], ['id' => 'b']]);
});

it('stubPages() returns the fake (fluent)', function (): void {
    $fake = AsaasClient::fake();

    expect($fake->stubPages('payments', [['data' => []]]))->toBe($fake);
});

it('stubPages does not match unrelated endpoints (pattern is scoped, not global)', function (): void {
    // Register stubPages FIRST with an unrelated pattern. If ConcatRemoveLeft
    // collapses the appended pattern to bare '*', this stub would gobble the
    // payments request before the explicit `payments` stub is consulted, and
    // the unmatched request would raise NoMatchingStubException (no stub
    // registered for /payments path under the resulting pattern).
    $fake = AsaasClient::fake()
        ->stubPages('webhooks', [
            ['data' => [['id' => 'wh_1']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0],
        ])
        ->stub('payments', ['id' => 'pay_real']);

    $payment = $fake->payments()->create([
        'value' => 1, 'customer' => 'c', 'billingType' => 'PIX', 'dueDate' => '2026-01-01',
    ]);

    // Payments endpoint must hit its own stub, not the webhooks pages sequence.
    expect($payment->success)->toBeTrue();
    expect($payment->data['id'])->toBe('pay_real');
});

it('stubPages without trailing * appends * exactly once (mutation-pin)', function (): void {
    // The pattern must support exactly one entry through pagination (with the
    // appended *) — and an unrelated request to /payments must NOT match.
    $fake = AsaasClient::fake()
        ->stubPages('webhooks', [
            ['data' => [['id' => 'a']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0],
        ]);

    // Unrelated pattern without explicit stub triggers NoMatchingStubException
    // — proving the stubPages pattern was scoped to webhooks*, not bare *.
    expect(fn () => $fake->payments()->find('pay_1'))
        ->toThrow(NoMatchingStubException::class);
});

it('stubPages with leading-* pattern preserves leading * (no extra append)', function (): void {
    // Pattern starts with * but does NOT end with *: original code appends '*'
    // (becomes '*webhooks*'), pinning StrEndsWithToStrStartsWith mutation,
    // which would skip the append and leave '*webhooks' (URL-suffix-only match).
    // The recorded request URL ends with '/webhooks?...' — both patterns match
    // this end, but the suffix-bound pattern would *fail* if a request had
    // anything after 'webhooks' (query string is part of url()). Use a
    // sub-path 'webhooks/list' style request to differentiate; the easiest
    // observable difference: when pattern is '*webhooks' (no append), the
    // paginated request to /v3/webhooks?limit=10 would NOT match because
    // the URL ends with '...?limit=10', not 'webhooks'.
    $fake = AsaasClient::fake()->stubPages('*webhooks', [
        ['data' => [['id' => 'a']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0],
    ]);

    $items = iterator_to_array($fake->webhooks()->all(['limit' => 10]), preserve_keys: false);

    // If StrEndsWithToStrStartsWith mutation were applied, '*webhooks' would
    // not match a URL ending with '?limit=10', so the iterator would fail.
    expect($items)->toBe([['id' => 'a']]);
});

it('stubPages() infers hasMore on every page but the last', function (): void {
    // Inferring the envelope page by page marks every page hasMore=false, which
    // stops next() after the first one — the exact walk stubPages() exists to
    // drive. Only the last page ends the walk.
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']]],
        ['data' => [['id' => 'b']]],
        ['data' => [['id' => 'c']]],
    ]);

    $items = iterator_to_array($fake->payments()->all(['limit' => 1]), preserve_keys: false);

    expect($items)->toBe([['id' => 'a'], ['id' => 'b'], ['id' => 'c']]);
});

it('stubPages() describes the walk, not the page, in totalCount and offset', function (): void {
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a'], ['id' => 'b']]],
        ['data' => [['id' => 'c']]],
    ]);

    $first = $fake->payments()->list(['limit' => 2]);

    expect($first->hasMore)->toBeTrue();
    expect($first->totalCount)->toBe(3);
    expect($first->limit)->toBe(2);
    expect($first->offset)->toBe(0);

    $second = $first->next();

    expect($second)->not->toBeNull();
    expect($second->hasMore)->toBeFalse();
    expect($second->totalCount)->toBe(3);
    expect($second->offset)->toBe(2);
});

it('stubPages() infers a single page as the end of the walk', function (): void {
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']]],
    ]);

    expect($fake->payments()->list()->hasMore)->toBeFalse();
});

it('stubPages() still infers hasMore when a page declares its own totalCount', function (): void {
    // `hasMore` is a property of the position in the sequence, never of the page
    // in isolation — a page volunteering `totalCount` must not suppress it, or
    // the walk stops on the first page.
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']], 'totalCount' => 2],
        ['data' => [['id' => 'b']]],
    ]);

    $ids = [];

    foreach ($fake->payments()->all() as $row) {
        $ids[] = $row['id'];
    }

    expect($ids)->toBe(['a', 'b']);
});

it('stubPages() keeps a page-declared totalCount over the inferred one', function (): void {
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']], 'totalCount' => 99],
        ['data' => [['id' => 'b']]],
    ]);

    $first = $fake->payments()->list();

    expect($first->totalCount)->toBe(99);
    expect($first->hasMore)->toBeTrue();
});

it('terminates ->all() on a lone stub that declares hasMore=true', function (): void {
    // A lone stub is one response replayed for every request, so it describes
    // page one only: without a terminal page the walk re-requests the same rows
    // forever and hangs the suite.
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1']],
        'hasMore' => true,
        'totalCount' => 5,
    ]);

    $ids = [];

    foreach ($fake->payments()->all() as $row) {
        $ids[] = $row['id'];
    }

    expect($ids)->toBe(['pay_1']);
});

it('keeps the declared envelope on the first page of a hasMore=true stub', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1']],
        'hasMore' => true,
        'totalCount' => 5,
        'limit' => 1,
    ]);

    $result = $fake->payments()->list();

    expect($result->hasMore)->toBeTrue();
    expect($result->totalCount)->toBe(5);
    expect($result->data)->toBe([['id' => 'pay_1']]);
});

it('serves the terminal page to any request past the first on a hasMore=true stub', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1']],
        'hasMore' => true,
        'totalCount' => 5,
        'limit' => 1,
    ]);

    $result = $fake->payments()->list(['offset' => 1]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
    expect($result->hasMore)->toBeFalse();
    // Held to the row the fake actually served, not the 5 the stub declared:
    // the page that ends a walk is the one `all()` checks its own tally
    // against, and repeating the 5 here would make the fake emit
    // `PAGINATION_SHORT` on a stub the test wrote as coherent.
    expect($result->totalCount)->toBe(1);
    expect($result->limit)->toBe(1);
    expect($result->offset)->toBe(1);
});

it('derives the terminal envelope from the rows when the stub declares only hasMore', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1'], ['id' => 'pay_2']],
        'hasMore' => true,
    ]);

    $result = $fake->payments()->list(['offset' => 2]);

    expect($result->data)->toBe([]);
    expect($result->totalCount)->toBe(2);
    expect($result->limit)->toBe(2);
});

it('treats a non-numeric offset as page one on a hasMore=true stub', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1']],
        'hasMore' => true,
    ]);

    $result = $fake->payments()->list(['offset' => 'not-a-number']);

    expect($result->data)->toBe([['id' => 'pay_1']]);
});

it('replays a lone stub that declares hasMore=false at any offset', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'pay_1']],
        'hasMore' => false,
    ]);

    expect($fake->payments()->list(['offset' => 7])->data)->toBe([['id' => 'pay_1']]);
});

it('rejects an empty stubPages() sequence at registration', function (): void {
    expect(fn (): FakeAsaasClient => AsaasClient::fake()->stubPages('payments', []))
        ->toThrow(InvalidArgumentException::class, 'stubPages() requires at least one page');
});

it('rejects a stubPages() count the walk exhausts before the last page', function (): void {
    // The fake used to force `hasMore: true` onto the non-final page while
    // honouring its declared totalCount, manufacturing the contradiction that
    // all() reports: row `a` came out, then a PAGINATION_INCONSISTENT error
    // object mid-row-stream, and row `b` was never delivered at all.
    expect(fn (): FakeAsaasClient => AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']], 'totalCount' => 1],
        ['data' => [['id' => 'b']]],
    ]))->toThrow(InvalidArgumentException::class, 'stubPages() page 1 declares totalCount 1');
});

it('catches an exhausted count on a later page, past pages that declare none', function (): void {
    // The pages before it declare no count and are simply passed over — the
    // check keeps walking rather than stopping at the first one it cannot
    // fault. Page 2 is the offender, and one page still waits behind it.
    expect(fn (): FakeAsaasClient => AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']]],
        ['data' => [['id' => 'b']], 'totalCount' => 2],
        ['data' => [['id' => 'c']]],
    ]))->toThrow(
        InvalidArgumentException::class,
        'stubPages() page 2 declares totalCount 2, which the walk has already delivered by the end of that page, while 1 more page(s) wait behind it.',
    );
});

it('leaves a zero count alone, which is the value an envelope omitting it reports', function (): void {
    // `all()` cannot read a totalCount of 0 as "the set is complete" — it is
    // what an envelope without the key reports — so it never stops the walk
    // early and there is no contradiction to refuse.
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']], 'totalCount' => 0],
        ['data' => [['id' => 'b']]],
    ]);

    expect(iterator_to_array($fake->payments()->all()))->toBe([['id' => 'a'], ['id' => 'b']]);
});

it('keeps serving a stubPages() count that describes the whole walk', function (): void {
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']], 'totalCount' => 2],
        ['data' => [['id' => 'b']], 'totalCount' => 2],
    ]);

    expect(iterator_to_array($fake->payments()->all()))->toBe([['id' => 'a'], ['id' => 'b']]);
});

it('leaves a last-page count alone, since no page waits behind it', function (): void {
    // The last page ends the walk on its own — a count it has already delivered
    // is the normal, coherent case rather than a contradiction.
    $fake = AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']]],
        ['data' => [['id' => 'b']], 'totalCount' => 2],
    ]);

    expect(iterator_to_array($fake->payments()->all()))->toBe([['id' => 'a'], ['id' => 'b']]);
});

it('serves a stub that models a later page to the request that asks for it', function (): void {
    // A stub declaring `offset: 10` describes page two. Cutting on a literal
    // offset 0 would hand the test an empty page it never described.
    $fake = AsaasClient::fake(['payments' => [
        'hasMore' => true,
        'totalCount' => 30,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'p11'], ['id' => 'p12']],
    ]]);

    $result = $fake->payments()->list(['offset' => 10, 'limit' => 10]);

    expect($result->data)->toBe([['id' => 'p11'], ['id' => 'p12']])
        ->and($result->hasMore)->toBeTrue();
});

it('still terminates the walk from a stub that models a later page', function (): void {
    $fake = AsaasClient::fake(['payments' => [
        'hasMore' => true,
        'totalCount' => 30,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'p11'], ['id' => 'p12']],
    ]]);

    $second = $fake->payments()->list(['offset' => 10, 'limit' => 10])->next();

    expect($second)->not->toBeNull()
        ->and($second->data)->toBe([])
        ->and($second->hasMore)->toBeFalse();
});

it('reads a stub-declared offset that arrives as a string', function (): void {
    // Fixtures pasted from a raw JSON capture can carry `"offset": "10"`.
    $fake = AsaasClient::fake(['payments' => [
        'hasMore' => true,
        'totalCount' => 30,
        'limit' => 10,
        'offset' => '10',
        'data' => [['id' => 'p11']],
    ]]);

    expect($fake->payments()->list(['offset' => 10, 'limit' => 10])->data)
        ->toBe([['id' => 'p11']]);
});
