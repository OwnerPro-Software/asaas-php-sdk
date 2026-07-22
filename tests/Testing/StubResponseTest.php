<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Testing\PageSequenceGuard;
use OwnerPro\Asaas\Testing\StubResponse;

mutates(StubResponse::class, PageSequenceGuard::class);

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

    // Key order is not part of the contract — the body is decoded as a map —
    // so the envelope is compared by content.
    expect($response->json())->toEqual([
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

    expect($response->json())->toEqual([
        'object' => 'custom',
        'hasMore' => false,
        'totalCount' => 1,
        'limit' => 7,
        'offset' => 14,
        'data' => [['id' => 'a']],
    ]);
});

it('preserves unknown top-level keys during inference', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'acc_1']],
        'walletId' => 'wallet-123',
        'customField' => 'kept',
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/accounts');

    expect($response->json())->toEqual([
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 1,
        'limit' => 1,
        'offset' => 0,
        'data' => [['id' => 'acc_1']],
        'walletId' => 'wallet-123',
        'customField' => 'kept',
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

it('replays a stub that declares no hasMore at every offset', function (): void {
    // Only `hasMore: true` promises a page a single response cannot serve; a
    // stub that never claims one keeps its envelope at any offset.
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'totalCount' => 99,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x?offset=3');

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

it('serves a hasMore=true stub as page one at offset zero', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'hasMore' => true,
        'totalCount' => 9,
        'limit' => 7,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x');

    expect($response->json())->toBe([
        'data' => [['id' => 'a']],
        'hasMore' => true,
        'totalCount' => 9,
        'limit' => 7,
    ]);
});

it('serves the terminal page past page one, keeping the declared envelope', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'hasMore' => true,
        'totalCount' => 9,
        'limit' => 7,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x?offset=1');

    // toEqual, not toBe: the terminal page merges the walk position over the
    // stub's own envelope, so key order follows the stub. Order carries no
    // meaning in a JSON object and pinning it would fail on a rearrangement
    // that changes nothing. Not toEqualCanonicalizing, which would also sort
    // nested arrays and stop pinning row order the day `data` is non-empty.
    //
    // `limit: 7` is the stub's and survives; `totalCount: 9` does not. The
    // terminal page ends the walk, and a page ending a walk after one row while
    // claiming nine is the contradiction `PAGINATION_SHORT` reports — the fake
    // would be manufacturing it. The declared 9 still reaches `->list()`, which
    // reads the stub's own page.
    expect($response->json())->toEqual([
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 1,
        'limit' => 7,
        'offset' => 1,
        'data' => [],
    ]);
});

it('derives the terminal totalCount and limit from the rows when undeclared', function (): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a'], ['id' => 'b']],
        'hasMore' => true,
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x?offset=5');

    expect($response->json())->toEqual([
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 2,
        'limit' => 2,
        'offset' => 5,
        'data' => [],
    ]);
});

it('keeps the envelope the stub declared on the terminal page too', function (): void {
    // Same rule inferPagination() states: a caller who put `object` or an extra
    // top-level field on the stub is describing the endpoint, and a page that
    // drops them is a page their assertions cannot recognise.
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'object' => 'refundList',
        'data' => [['id' => 'a']],
        'hasMore' => true,
        'requestUrl' => 'https://asaas.test/refund/abc',
    ])]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/x?offset=1');

    expect($response->json())->toEqual([
        'object' => 'refundList',
        'requestUrl' => 'https://asaas.test/refund/abc',
        'hasMore' => false,
        'totalCount' => 1,
        'limit' => 1,
        'offset' => 1,
        'data' => [],
    ]);
});

it('treats an absent, empty or non-numeric offset as page one', function (string $url): void {
    $factory = new Factory;
    $factory->fake(['*' => StubResponse::normalize([
        'data' => [['id' => 'a']],
        'hasMore' => true,
    ])]);

    $response = $factory->createPendingRequest()->get($url);

    expect($response->json())->toBe(['data' => [['id' => 'a']], 'hasMore' => true]);
})->with([
    'no query string' => ['https://example.test/api/v3/x'],
    'other filters only' => ['https://example.test/api/v3/x?limit=10'],
    'non-numeric offset' => ['https://example.test/api/v3/x?offset=abc'],
    'empty offset' => ['https://example.test/api/v3/x?offset='],
    'explicit zero' => ['https://example.test/api/v3/x?offset=0'],
]);

it('rejects an empty stubPages() sequence', function (): void {
    expect(fn (): array => StubResponse::normalizePages([]))
        ->toThrow(InvalidArgumentException::class, 'stubPages() requires at least one page');
});

it('normalizes a stubPages sequence whose keys are not sequential', function (): void {
    // `array_filter($fixtures, ...)` preserves keys, so a filtered fixture list
    // arrives keyed 0 and 2 — the idiomatic way to build one. Read positionally
    // as it was, the second page fell outside the sequence's own bookkeeping:
    // it was never recognised as the last, so it kept `hasMore: true` and the
    // walk ran off the end of the sequence into Laravel's raw "response
    // sequence is empty" — the failure normalizePages() exists to prevent.
    $pages = StubResponse::normalizePages([0 => ['data' => [['id' => 'a']]], 2 => ['data' => [['id' => 'b']]]]);

    $factory = new Factory;
    $factory->fake(['*' => $factory->sequence()->pushResponse($pages[0])->pushResponse($pages[1])]);

    $first = $factory->createPendingRequest()->get('https://example.test/api/v3/x')->json();
    $second = $factory->createPendingRequest()->get('https://example.test/api/v3/x?offset=1')->json();

    expect($first['hasMore'])->toBeTrue();
    expect($second['hasMore'])->toBeFalse();
    expect($second['totalCount'])->toBe(2);
});

it('leaves a stubPages entry with no data key untouched', function (): void {
    // A body with no `data` is not a page of a walk — inference treats it as an
    // opaque response, and the sequence has no verdict to impose on it.
    $factory = new Factory;
    $factory->fake(['*' => $factory->sequence()->pushResponse(
        StubResponse::normalizePages([['id' => 'pay_1', 'status' => 'CONFIRMED']])[0],
    )]);

    $response = $factory->createPendingRequest()->get('https://example.test/api/v3/payments');

    expect($response->json())->toBe(['id' => 'pay_1', 'status' => 'CONFIRMED']);
});
