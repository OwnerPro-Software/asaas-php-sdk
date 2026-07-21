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

it('stubPages() leaves a declared envelope untouched', function (): void {
    $fake = AsaasClient::fake()->stubPages('webhooks', [
        ['data' => [['id' => 'a']], 'hasMore' => true, 'totalCount' => 9],
    ]);

    $result = $fake->webhooks()->list();

    expect($result->totalCount)->toBe(9);
    expect($result->hasMore)->toBeTrue();
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
