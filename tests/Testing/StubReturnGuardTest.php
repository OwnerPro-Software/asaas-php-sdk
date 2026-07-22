<?php

declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Testing\StubReturnGuard;

mutates(StubReturnGuard::class);

/**
 * A closure stub that returns a falsy value used to travel to the real Asaas
 * API: Laravel collects stub returns through `->filter()`, so the value was
 * dropped, the absence read as "no stub matched", and the request handed to the
 * live network handler. Every one of those shapes must now resolve inside the
 * fake — either as a response, or as a refusal that names the mistake.
 */
it('refuses a closure stub that returns null instead of sending the request', function (): void {
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): mixed => null);

    expect(fn (): mixed => $fake->payments()->find('pay_1'))
        ->toThrow(InvalidArgumentException::class, 'returned null');
});

it('names the offending pattern and points at the empty-200 spelling', function (): void {
    $fake = AsaasClient::fake()->stub('payments/*', fn (): mixed => null);

    try {
        $fake->payments()->find('pay_1');

        expect(false)->toBeTrue('Expected InvalidArgumentException');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())
            ->toContain('payments/*')
            ->toContain('Factory::response()');
    }
});

it('refuses every other non-body return shape', function (mixed $returned, string $type): void {
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): mixed => $returned);

    expect(fn (): mixed => $fake->payments()->find('pay_1'))
        ->toThrow(InvalidArgumentException::class, 'returned '.$type);
})->with([
    'false' => [false, 'bool'],
    'zero' => [0, 'int'],
    'float' => [1.5, 'float'],
    'object' => [new stdClass, 'stdClass'],
]);

it('serves an array body returned by a closure', function (): void {
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): array => ['id' => 'pay_1']);

    expect($fake->payments()->find('pay_1')->data['id'])->toBe('pay_1');
});

it('serves a string body returned by a closure', function (): void {
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): string => '{"id":"pay_1"}');

    expect($fake->payments()->find('pay_1')->data['id'])->toBe('pay_1');
});

it('serves a Response returned by a closure, keeping status and headers', function (): void {
    $fake = AsaasClient::fake()->stub(
        'payments',
        fn (): Response => new Response(
            new GuzzleHttp\Psr7\Response(201, ['X-Trace' => 'abc'], '{"id":"pay_new"}'),
        ),
    );

    $result = $fake->payments()->create(['customer' => 'cus_1', 'billingType' => 'PIX', 'value' => 10.0, 'dueDate' => '2026-01-01']);

    expect($result->data['id'])->toBe('pay_new');
    expect($result->response?->status())->toBe(201);
    expect($result->response?->header('X-Trace'))->toBe('abc');
});

it('serves an empty array body returned by a closure', function (): void {
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): array => []);

    expect($fake->payments()->find('pay_1')->data)->toBe([]);
});

it('treats an empty string body as the unreadable 200 it is, not as a stray request', function (): void {
    // `''` is falsy, so it used to escape to the network. It is a legitimate
    // body, so it is served — and an empty 200 is exactly what the SDK reports
    // as indeterminate, which is the outcome being pinned here.
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): string => '');

    expect(fn (): mixed => $fake->payments()->find('pay_1'))
        ->toThrow(IndeterminateResultException::class);
});

it('passes a PromiseInterface through untouched', function (): void {
    $promise = Http::response(['id' => 'pay_1']);
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): mixed => $promise);

    expect($fake->payments()->find('pay_1')->data['id'])->toBe('pay_1');
});

it('leaves no recording for a refused stub, as any throwing closure does', function (): void {
    // The guard raises where the closure itself runs — inside the stub
    // callback, before Laravel's recorder sees the exchange. That matches what
    // `stubException()` already documents for a closure that throws, and costs
    // nothing here: the refusal is an unmissable exception, not a silent pass.
    $fake = AsaasClient::fake()->stub('payments/pay_1', fn (): mixed => null);

    try {
        $fake->payments()->find('pay_1');
    } catch (InvalidArgumentException) {
        // the refusal itself is pinned above
    }

    $fake->assertNothingSent();
});

it('normalizes directly, without a client, for every accepted shape', function (): void {
    expect(StubReturnGuard::normalize(Factory::response(['a' => 1]), 'p'))
        ->toBeInstanceOf(PromiseInterface::class);
    expect(StubReturnGuard::normalize(['a' => 1], 'p'))
        ->toBeInstanceOf(PromiseInterface::class);
    expect(StubReturnGuard::normalize('body', 'p'))
        ->toBeInstanceOf(PromiseInterface::class);
});
