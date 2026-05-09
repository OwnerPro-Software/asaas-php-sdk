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
