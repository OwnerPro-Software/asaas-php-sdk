<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Testing\FakeAsaasClient;

mutates(FakeAsaasClient::class);

it('stub() registers a successful response post-construction', function (): void {
    $fake = AsaasClient::fake()->stub('payments/*', ['id' => 'pay_1']);

    $result = $fake->payments()->find('pay_1');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_1');
});

it('stub() returns the fake (fluent)', function (): void {
    $fake = AsaasClient::fake();

    expect($fake->stub('payments', []))->toBe($fake);
});

it('stubError() registers a non-2xx response with errors body', function (): void {
    $fake = AsaasClient::fake()->stubError(
        'payments',
        status: 400,
        body: ['errors' => [['code' => 'invalid_value', 'description' => 'bad input']]],
    );

    $result = $fake->payments()->create([
        'value' => -1,
        'customer' => 'c',
        'billingType' => 'PIX',
        'dueDate' => '2026-01-01',
    ]);

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'invalid_value', 'description' => 'bad input']]);
});

it('stubException() simulates a connection failure', function (): void {
    $fake = AsaasClient::fake()->stubException(
        'payments/*',
        new ConnectionException('timeout'),
    );

    expect(fn () => $fake->payments()->find('pay_1'))->toThrow(IndeterminateResultException::class);
});

it('supports glob patterns with * (single segment)', function (): void {
    $fake = AsaasClient::fake()->stub('payments/*', ['id' => 'matched']);

    $result = $fake->payments()->find('pay_xyz');

    expect($result->data['id'])->toBe('matched');
});

it('supports Http::sequence() for multi-call stubs', function (): void {
    $fake = AsaasClient::fake([
        'payments/*' => Http::sequence()
            ->push(['id' => 'pay_1'])
            ->push(['id' => 'pay_2']),
    ]);

    $a = $fake->payments()->find('pay_1');
    $b = $fake->payments()->find('pay_1');

    expect($a->data['id'])->toBe('pay_1');
    expect($b->data['id'])->toBe('pay_2');
});

it('accepts Http::sequence() through the fluent stub() too', function (): void {
    $fake = AsaasClient::fake()->stub(
        'payments/*',
        Http::sequence()
            ->push(['id' => 'pay_1'])
            ->push(['id' => 'pay_2']),
    );

    $a = $fake->payments()->find('pay_1');
    $b = $fake->payments()->find('pay_1');

    expect($a->data['id'])->toBe('pay_1');
    expect($b->data['id'])->toBe('pay_2');
});

it('stubError() forwards response headers to the recorded response', function (): void {
    $fake = AsaasClient::fake()->stubError(
        'payments',
        status: 429,
        body: ['errors' => [['code' => 'rate_limit']]],
        headers: ['Retry-After' => '30'],
    );

    $fake->payments()->create([
        'value' => 100,
        'customer' => 'c',
        'billingType' => 'PIX',
        'dueDate' => '2026-01-01',
    ]);

    $response = $fake->recorded()[0][1];
    expect($response->status())->toBe(429);
    expect($response->header('Retry-After'))->toBe('30');
});

it('FakeAsaasClient constructor accepts environment as a string', function (): void {
    $fake = new FakeAsaasClient(['payments/*' => ['id' => 'p']], 'production');

    $fake->payments()->find('pay_1');

    expect($fake->recorded()[0][0]->url())
        ->toBe('https://api.asaas.com/v3/payments/pay_1');
});

it('replaces the stub an equivalent pattern already registered', function (string $second): void {
    // The map used to be keyed by the raw string, so these spellings were
    // separate entries collapsing onto one glob: the first registered won and
    // the later one sat there dead — accepted, never served, and never
    // reported, since a match by some entry keeps NoMatchingStubException away.
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'FIRST']])->stub($second, ['id' => 'SECOND']);

    expect($fake->payments()->find('pay_1')->data['id'])->toBe('SECOND');
})->with(['payments/*', '/payments/*', ' payments/*']);

it('keeps the position of the stub an equivalent pattern replaces', function (): void {
    // Order decides ties, so a replacement must not jump the queue: the broad
    // stub was registered first and still wins over the specific one behind it.
    $fake = AsaasClient::fake([
        'payments/*' => ['id' => 'BROAD'],
        'payments/pay_1' => ['id' => 'SPECIFIC'],
    ])->stub('/payments/*', ['id' => 'BROAD_AGAIN']);

    expect($fake->payments()->find('pay_1')->data['id'])->toBe('BROAD_AGAIN');
    expect($fake->payments()->find('pay_2')->data['id'])->toBe('BROAD_AGAIN');
});
