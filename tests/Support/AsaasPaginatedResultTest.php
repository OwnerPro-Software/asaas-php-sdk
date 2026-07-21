<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\RawResponse;

mutates(AsaasPaginatedResult::class);

it('creates a successful paginated result', function (): void {
    $items = [['id' => 'a'], ['id' => 'b']];
    $response = RawResponse::fake(200);

    $result = AsaasPaginatedResult::success(
        data: $items,
        totalCount: 10,
        hasMore: true,
        limit: 2,
        offset: 0,
        rawResponse: $response,
        nextPageFetcher: fn (int $offset) => null,
    );

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]['id'])->toBe('a');
    expect($result->totalCount)->toBe(10);
    expect($result->hasMore)->toBeTrue();
    expect($result->limit)->toBe(2);
    expect($result->offset)->toBe(0);
    expect($result->response)->toBe($response);
    expect($result->errors)->toBeNull();
});

it('creates a failed paginated result with response', function (): void {
    $errors = [['description' => 'Unauthorized']];
    $response = RawResponse::fake(401);
    $result = AsaasPaginatedResult::failure($errors, $response);

    expect($result->success)->toBeFalse();
    expect($result->data)->toBe([]);
    expect($result->errors)->toBe($errors);
    expect($result->response)->toBe($response);
    expect($result->response->status())->toBe(401);
    expect($result->totalCount)->toBe(0);
    expect($result->hasMore)->toBeFalse();
    expect($result->limit)->toBe(0);
    expect($result->offset)->toBe(0);
});

it('creates a failed paginated result with null response on connection error', function (): void {
    $errors = [['code' => 'CONNECTION_ERROR', 'description' => 'Timed out']];
    $result = AsaasPaginatedResult::failure($errors);

    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
});

it('next() fetches the next page when hasMore is true', function (): void {
    $nextResult = AsaasPaginatedResult::success(
        data: [['id' => 'c']],
        totalCount: 10,
        hasMore: false,
        limit: 2,
        offset: 2,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: fn (int $offset) => null,
    );

    $result = AsaasPaginatedResult::success(
        data: [['id' => 'a'], ['id' => 'b']],
        totalCount: 10,
        hasMore: true,
        limit: 2,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: fn (int $offset) => $nextResult,
    );

    $next = $result->next();

    expect($next)->not->toBeNull();
    expect($next->data[0]['id'])->toBe('c');
    expect($next->offset)->toBe(2);
});

it('next() returns null when hasMore is false', function (): void {
    $result = AsaasPaginatedResult::success(
        data: [],
        totalCount: 0,
        hasMore: false,
        limit: 10,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    expect($result->next())->toBeNull();
});

it('next() returns null when nextPageFetcher is null even with hasMore true', function (): void {
    $result = AsaasPaginatedResult::success(
        data: [['id' => 'a']],
        totalCount: 10,
        hasMore: true,
        limit: 1,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    expect($result->next())->toBeNull();
});

it('next() returns null on an empty page to prevent infinite loop', function (): void {
    $result = AsaasPaginatedResult::success(
        data: [],
        totalCount: 10,
        hasMore: true,
        limit: 10,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: fn (int $offset) => throw new RuntimeException('must not fetch'),
    );

    expect($result->next())->toBeNull();
});

it('next() still advances when the envelope omits limit', function (): void {
    $receivedOffset = null;

    $result = AsaasPaginatedResult::success(
        data: [['id' => 'a']],
        totalCount: 10,
        hasMore: true,
        limit: 0,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: function (int $offset) use (&$receivedOffset): AsaasPaginatedResult {
            $receivedOffset = $offset;

            return AsaasPaginatedResult::success(
                data: [],
                totalCount: 10,
                hasMore: false,
                limit: 0,
                offset: $offset,
                rawResponse: RawResponse::fake(200),
                nextPageFetcher: null,
            );
        },
    );

    expect($result->next())->not->toBeNull();
    expect($receivedOffset)->toBe(1);
});

it('next() passes correct offset to fetcher', function (): void {
    $receivedOffset = null;
    $nextResult = AsaasPaginatedResult::success(
        data: [['id' => 'f']],
        totalCount: 10,
        hasMore: false,
        limit: 5,
        offset: 5,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    $result = AsaasPaginatedResult::success(
        data: [['id' => 'a'], ['id' => 'b'], ['id' => 'c'], ['id' => 'd'], ['id' => 'e']],
        totalCount: 10,
        hasMore: true,
        limit: 5,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: function (int $offset) use (&$receivedOffset, $nextResult): AsaasPaginatedResult {
            $receivedOffset = $offset;

            return $nextResult;
        },
    );

    $next = $result->next();

    expect($next)->not->toBeNull();
    expect($receivedOffset)->toBe(5);
});

it('orFail() throws on failure', function (): void {
    $response = RawResponse::fake(403);
    $result = AsaasPaginatedResult::failure([['description' => 'Forbidden']], $response);

    try {
        $result->orFail();
    } catch (AsaasRequestException $e) {
        expect($e->getMessage())->toBe('Forbidden');
        expect($e->getCode())->toBe(403);
        expect($e->statusCode)->toBe(403);
        expect($e->response)->toBe($response);

        return;
    }

    test()->fail('Expected AsaasRequestException was not thrown');
});

it('orFail() returns self on success', function (): void {
    $result = AsaasPaginatedResult::success(
        data: [],
        totalCount: 0,
        hasMore: false,
        limit: 10,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    expect($result->orFail())->toBe($result);
});

// A page of `GET /webhooks` carries one live `authToken` per row.
it('scrubs the per-row credential in debug output', function (): void {
    $result = AsaasPaginatedResult::success(
        data: [
            ['id' => 'w_1', 'authToken' => 'row-secret-one'],
            ['id' => 'w_2', 'authToken' => 'row-secret-two'],
        ],
        totalCount: 2,
        hasMore: false,
        limit: 10,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    expect($result->__debugInfo()['data'])->toBe([
        ['id' => 'w_1', 'authToken' => '***'],
        ['id' => 'w_2', 'authToken' => '***'],
    ])
        ->and(print_r($result, true))->not->toContain('row-secret-one');
});

it('reports every paginated field verbatim in debug output, omitting the private cursor', function (): void {
    $response = RawResponse::fake(200);
    $result = AsaasPaginatedResult::success(
        data: [['id' => 'w_1']],
        totalCount: 7,
        hasMore: true,
        limit: 5,
        offset: 20,
        rawResponse: $response,
        nextPageFetcher: fn (int $offset): AsaasPaginatedResult => AsaasPaginatedResult::failure([]),
    );

    expect($result->__debugInfo())->toBe([
        'success' => true,
        'data' => [['id' => 'w_1']],
        'totalCount' => 7,
        'hasMore' => true,
        'limit' => 5,
        'offset' => 20,
        'errors' => null,
        'response' => $response,
    ]);
});

it('reports the failure fields verbatim in debug output', function (): void {
    $result = AsaasPaginatedResult::failure([['code' => 'x']], null, offset: 20, limit: 5);

    expect($result->__debugInfo())->toBe([
        'success' => false,
        'data' => [],
        'totalCount' => 0,
        'hasMore' => false,
        'limit' => 5,
        'offset' => 20,
        'errors' => [['code' => 'x']],
        'response' => null,
    ]);
});

it('scrubs a credential carried as a field on a canonical error row', function (): void {
    // Same two-layer reasoning as AsaasResult: ErrorEnvelope covers a
    // credential pasted into a synthesized description, this covers one
    // carried as a field on a row Asaas passed through.
    $result = AsaasPaginatedResult::failure(
        [['code' => 'x', 'description' => 'see key', 'apiKey' => 'aact_prod_LIVEKEY123']],
        RawResponse::fake(status: 400),
    );

    expect(json_encode($result))->not->toContain('aact_prod_LIVEKEY123');
    expect($result->__debugInfo()['errors'])
        ->toBe([['code' => 'x', 'description' => 'see key', 'apiKey' => '***']]);
});

it('leaves a null errors list null on a successful page', function (): void {
    $result = AsaasPaginatedResult::success(
        data: [['id' => 'pay_1']],
        totalCount: 1,
        hasMore: false,
        limit: 10,
        offset: 0,
        rawResponse: RawResponse::fake(),
        nextPageFetcher: null,
    );

    expect($result->__debugInfo()['errors'])->toBeNull();
});
