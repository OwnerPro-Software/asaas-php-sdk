<?php

declare(strict_types=1);

use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Testing\PageEnvelopeGuard;

mutates(PageEnvelopeGuard::class);

/**
 * The point of the guard is *where* the failure lands: without it these
 * fixtures register happily and blow up inside the call under test, blaming the
 * SDK for a defect that is in the test's own envelope.
 */
it('rejects a lone stub whose pagination key the SDK cannot read', function (array $body): void {
    AsaasClient::fake()->stub('payments', $body);
})
    ->throws(InvalidArgumentException::class, 'stub() declares')
    ->with([
        'totalCount as a numeric string' => [['data' => [['id' => 'a']], 'totalCount' => '1']],
        'limit as a numeric string' => [['data' => [['id' => 'a']], 'limit' => '10']],
        'hasMore as a string' => [['data' => [['id' => 'a']], 'hasMore' => 'true']],
    ]);

it('names the page whose envelope it rejects in a sequence', function (): void {
    AsaasClient::fake()->stubPages('payments', [
        ['data' => [['id' => 'a']]],
        ['data' => [['id' => 'b']], 'totalCount' => '2'],
    ]);
})->throws(InvalidArgumentException::class, 'stubPages() page 2 declares totalCount as string');

it('states the type the envelope expects', function (): void {
    AsaasClient::fake()->stub('payments', ['data' => [], 'hasMore' => 1]);
})->throws(InvalidArgumentException::class, 'declares hasMore as int, but the pagination envelope types it as bool');

/**
 * A stub of a single resource is an opaque body the walk never reads. Holding
 * it to the envelope's types would refuse fixtures the SDK handles fine.
 */
it('leaves a body that is not a page alone', function (): void {
    $fake = AsaasClient::fake()->stub('payments/pay_1', [
        'id' => 'pay_1',
        'data' => ['nested' => 'object'],
        'limit' => '500.00',
    ]);

    $result = $fake->payments()->find('pay_1');

    expect($result->success)->toBeTrue();
    expect($result->data['limit'])->toBe('500.00');
});

/**
 * `offset` reaches no typed parameter — the SDK paginates by the offset it
 * asked for — so a loosely declared one stays legal.
 */
it('leaves a loosely declared offset alone', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'a']], 'hasMore' => true, 'offset' => '10',
    ]);

    $result = $fake->payments()->list(['offset' => 10]);

    expect($result->data)->toBe([['id' => 'a']]);
});

it('accepts a page that declares every key with the type the envelope reads', function (): void {
    $fake = AsaasClient::fake()->stub('payments', [
        'data' => [['id' => 'a']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 1, 'offset' => 0,
    ]);

    expect($fake->payments()->list()->totalCount)->toBe(1);
});
