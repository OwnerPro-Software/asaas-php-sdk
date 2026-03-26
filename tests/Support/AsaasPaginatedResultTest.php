<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\BaseDTO;

mutates(AsaasPaginatedResult::class);

final class PaginatedTestDTO extends BaseDTO
{
    public string $id;
}

it('creates a successful paginated result', function () {
    $items = [new PaginatedTestDTO(['id' => 'a']), new PaginatedTestDTO(['id' => 'b'])];

    $result = AsaasPaginatedResult::success(
        data: $items,
        totalCount: 10,
        hasMore: true,
        limit: 2,
        offset: 0,
        statusCode: 200,
        nextPageFetcher: fn (int $offset) => null,
    );

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]->id)->toBe('a');
    expect($result->totalCount)->toBe(10);
    expect($result->hasMore)->toBeTrue();
    expect($result->limit)->toBe(2);
    expect($result->offset)->toBe(0);
    expect($result->errors)->toBeNull();
});

it('creates a failed paginated result', function () {
    $errors = [['description' => 'Unauthorized']];
    $result = AsaasPaginatedResult::failure($errors, 401);

    expect($result->success)->toBeFalse();
    expect($result->data)->toBe([]);
    expect($result->errors)->toBe($errors);
    expect($result->statusCode)->toBe(401);
    expect($result->totalCount)->toBe(0);
    expect($result->hasMore)->toBeFalse();
    expect($result->limit)->toBe(0);
    expect($result->offset)->toBe(0);
});

it('next() fetches the next page when hasMore is true', function () {
    $nextResult = AsaasPaginatedResult::success(
        data: [new PaginatedTestDTO(['id' => 'c'])],
        totalCount: 10,
        hasMore: false,
        limit: 2,
        offset: 2,
        statusCode: 200,
        nextPageFetcher: fn (int $offset) => null,
    );

    $result = AsaasPaginatedResult::success(
        data: [new PaginatedTestDTO(['id' => 'a']), new PaginatedTestDTO(['id' => 'b'])],
        totalCount: 10,
        hasMore: true,
        limit: 2,
        offset: 0,
        statusCode: 200,
        nextPageFetcher: fn (int $offset) => $nextResult,
    );

    $next = $result->next();

    expect($next)->not->toBeNull();
    expect($next->data[0]->id)->toBe('c');
    expect($next->offset)->toBe(2);
});

it('next() returns null when hasMore is false', function () {
    $result = AsaasPaginatedResult::success(
        data: [],
        totalCount: 0,
        hasMore: false,
        limit: 10,
        offset: 0,
        statusCode: 200,
        nextPageFetcher: null,
    );

    expect($result->next())->toBeNull();
});

it('next() returns null when nextPageFetcher is null even with hasMore true', function () {
    $result = AsaasPaginatedResult::success(
        data: [new PaginatedTestDTO(['id' => 'a'])],
        totalCount: 10,
        hasMore: true,
        limit: 1,
        offset: 0,
        statusCode: 200,
        nextPageFetcher: null,
    );

    expect($result->next())->toBeNull();
});

it('next() passes correct offset to fetcher', function () {
    $receivedOffset = null;
    $nextResult = AsaasPaginatedResult::success(
        data: [new PaginatedTestDTO(['id' => 'c'])],
        totalCount: 10,
        hasMore: false,
        limit: 5,
        offset: 5,
        statusCode: 200,
        nextPageFetcher: null,
    );

    $result = AsaasPaginatedResult::success(
        data: [new PaginatedTestDTO(['id' => 'a'])],
        totalCount: 10,
        hasMore: true,
        limit: 5,
        offset: 0,
        statusCode: 200,
        nextPageFetcher: function (int $offset) use (&$receivedOffset, $nextResult): AsaasPaginatedResult {
            $receivedOffset = $offset;

            return $nextResult;
        },
    );

    $next = $result->next();

    expect($next)->not->toBeNull();
    expect($receivedOffset)->toBe(5);
});

it('throw() throws on failure', function () {
    $result = AsaasPaginatedResult::failure([['description' => 'Forbidden']], 403);

    try {
        $result->throw();
    } catch (AsaasRequestException $e) {
        expect($e->getMessage())->toBe('Forbidden');
        expect($e->getCode())->toBe(403);
        expect($e->statusCode)->toBe(403);

        return;
    }

    test()->fail('Expected AsaasRequestException was not thrown');
});

it('throw() returns self on success', function () {
    $result = AsaasPaginatedResult::success(
        data: [],
        totalCount: 0,
        hasMore: false,
        limit: 10,
        offset: 0,
        statusCode: 200,
        nextPageFetcher: null,
    );

    expect($result->throw())->toBe($result);
});
