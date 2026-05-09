<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Testing\StubResponse;

mutates(StubResponse::class);

it('wraps raw associative array as 200 JSON response', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize(['id' => 'pay_1'])]);

    $response = $factory->createPendingRequest()->post('https://example.test/api/v3/payments');

    expect($response->successful())->toBeTrue()
        ->and($response->json())->toBe(['id' => 'pay_1']);
});

it('infers hasMore=false and totalCount=count when shape has data only', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize(['data' => [['id' => 'a'], ['id' => 'b']]])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/payments');

    expect($response->json())->toBe([
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 2,
        'limit' => 2,
        'offset' => 0,
        'data' => [['id' => 'a'], ['id' => 'b']],
    ]);
});

it('respects explicit hasMore/totalCount overrides', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'hasMore' => true,
        'totalCount' => 42,
        'limit' => 10,
        'offset' => 20,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/payments');

    expect($response->json())->toBe([
        'data' => [['id' => 'a']],
        'hasMore' => true,
        'totalCount' => 42,
        'limit' => 10,
        'offset' => 20,
    ]);
});

it('preserves explicit object/limit/offset values during inference', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'object' => 'custom',
        'limit' => 7,
        'offset' => 14,
        'data' => [['id' => 'a']],
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/y');

    expect($response->json())->toBe([
        'object' => 'custom',
        'hasMore' => false,
        'totalCount' => 1,
        'limit' => 7,
        'offset' => 14,
        'data' => [['id' => 'a']],
    ]);
});

it('skips inference when data is not a list', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize(['data' => ['key' => 'value']])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x');

    expect($response->json())->toBe(['data' => ['key' => 'value']]);
});

it('skips inference when only hasMore is set', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'hasMore' => true,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x');

    expect($response->json())->toBe([
        'data' => [['id' => 'a']],
        'hasMore' => true,
    ]);
});

it('skips inference when only totalCount is set', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'totalCount' => 99,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x');

    expect($response->json())->toBe([
        'data' => [['id' => 'a']],
        'totalCount' => 99,
    ]);
});

it('passes through PromiseInterface stubs unchanged', function (): void {
    $promise = Http::response(['x' => 1], 201);

    expect(StubResponse::normalize($promise))->toBe($promise);
});

it('passes through closure stubs unchanged', function (): void {
    $closure = static fn (): Response => new Response(new GuzzleHttp\Psr7\Response(200, [], '{"x":1}'));

    expect(StubResponse::normalize($closure))->toBe($closure);
});
