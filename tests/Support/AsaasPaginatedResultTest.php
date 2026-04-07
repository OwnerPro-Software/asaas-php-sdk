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

it('next() returns null when limit is zero to prevent infinite loop', function (): void {
    $nextResult = AsaasPaginatedResult::success(
        data: [['id' => 'b']],
        totalCount: 10,
        hasMore: false,
        limit: 10,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    $result = AsaasPaginatedResult::success(
        data: [['id' => 'a']],
        totalCount: 10,
        hasMore: true,
        limit: 0,
        offset: 0,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: fn (int $offset) => $nextResult,
    );

    expect($result->next())->toBeNull();
});

it('next() passes correct offset to fetcher', function (): void {
    $receivedOffset = null;
    $nextResult = AsaasPaginatedResult::success(
        data: [['id' => 'c']],
        totalCount: 10,
        hasMore: false,
        limit: 5,
        offset: 5,
        rawResponse: RawResponse::fake(200),
        nextPageFetcher: null,
    );

    $result = AsaasPaginatedResult::success(
        data: [['id' => 'a']],
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
