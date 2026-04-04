<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\HttpConnector;
use OwnerPro\Asaas\Support\PaginatesResults;
use OwnerPro\Asaas\Support\RawResponse;

mutates(PaginatesResults::class);

function fakeConnector(Closure $getHandler): HttpConnector
{
    return new class($getHandler) implements HttpConnector
    {
        use PaginatesResults;

        /** @var Closure(string, array<string, mixed>): AsaasResult */
        private Closure $getHandler;

        public function __construct(Closure $getHandler)
        {
            $this->getHandler = $getHandler;
        }

        /** @param array<string, mixed> $query */
        public function get(string $path, array $query): AsaasResult
        {
            return ($this->getHandler)($path, $query);
        }

        /** @param array<string, mixed> $data */
        public function post(string $path, array $data): AsaasResult
        {
            return AsaasResult::failure([['code' => 'NOT_IMPLEMENTED', 'description' => 'Not implemented']]);
        }

        /** @param array<string, mixed> $data */
        public function put(string $path, array $data): AsaasResult
        {
            return AsaasResult::failure([['code' => 'NOT_IMPLEMENTED', 'description' => 'Not implemented']]);
        }

        public function delete(string $path): AsaasResult
        {
            return AsaasResult::failure([['code' => 'NOT_IMPLEMENTED', 'description' => 'Not implemented']]);
        }
    };
}

// --- paginate ---

it('paginate returns success with parsed pagination fields', function (): void {
    $connector = fakeConnector(fn (): AsaasResult => AsaasResult::success(
        ['data' => [['id' => 'pay_1']], 'totalCount' => 50, 'hasMore' => true, 'limit' => 10, 'offset' => 0],
        RawResponse::fake(),
    ));

    $result = $connector->paginate('/v3/payments', []);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([['id' => 'pay_1']]);
    expect($result->totalCount)->toBe(50);
    expect($result->hasMore)->toBeTrue();
    expect($result->limit)->toBe(10);
    expect($result->offset)->toBe(0);
});

it('paginate returns failure when get fails', function (): void {
    $connector = fakeConnector(fn (): AsaasResult => AsaasResult::failure(
        [['code' => 'ERROR', 'description' => 'Something broke']],
        RawResponse::fake(status: 500),
    ));

    $result = $connector->paginate('/v3/payments', []);

    expect($result->success)->toBeFalse();
    expect($result->errors[0]['description'])->toBe('Something broke');
    expect($result->response->status())->toBe(500);
});

it('paginate defaults missing fields to zero/empty/false', function (): void {
    $connector = fakeConnector(fn (): AsaasResult => AsaasResult::success(
        [],
        RawResponse::fake(),
    ));

    $result = $connector->paginate('/v3/payments', []);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
    expect($result->totalCount)->toBe(0);
    expect($result->hasMore)->toBeFalse();
    expect($result->limit)->toBe(0);
    expect($result->offset)->toBe(0);
});

it('paginate next() fetches the next page', function (): void {
    $callCount = 0;
    $connector = fakeConnector(function (string $path, array $query) use (&$callCount): AsaasResult {
        $callCount++;

        if ($callCount === 1) {
            return AsaasResult::success(
                ['data' => [['id' => 'p1']], 'totalCount' => 2, 'hasMore' => true, 'limit' => 1, 'offset' => 0],
                RawResponse::fake(),
            );
        }

        return AsaasResult::success(
            ['data' => [['id' => 'p2']], 'totalCount' => 2, 'hasMore' => false, 'limit' => 1, 'offset' => 1],
            RawResponse::fake(),
        );
    });

    $page1 = $connector->paginate('/v3/payments', ['limit' => 1]);
    $page2 = $page1->next();

    expect($page1->data)->toBe([['id' => 'p1']]);
    expect($page2)->not->toBeNull();
    expect($page2->data)->toBe([['id' => 'p2']]);
    expect($page2->next())->toBeNull();
});

// --- all ---

it('all iterates through all pages', function (): void {
    $callCount = 0;
    $connector = fakeConnector(function (string $path, array $query) use (&$callCount): AsaasResult {
        $callCount++;

        if ($callCount === 1) {
            return AsaasResult::success(
                ['data' => [['id' => 'p1'], ['id' => 'p2']], 'totalCount' => 3, 'hasMore' => true, 'limit' => 2, 'offset' => 0],
                RawResponse::fake(),
            );
        }

        return AsaasResult::success(
            ['data' => [['id' => 'p3']], 'totalCount' => 3, 'hasMore' => false, 'limit' => 2, 'offset' => 2],
            RawResponse::fake(),
        );
    });

    $items = iterator_to_array($connector->all('/v3/payments', ['limit' => 2]));

    expect($items)->toHaveCount(3);
    expect($items[0]['id'])->toBe('p1');
    expect($items[2]['id'])->toBe('p3');
});

it('all yields AsaasPaginatedError on failure', function (): void {
    $connector = fakeConnector(fn (): AsaasResult => AsaasResult::failure(
        [['code' => 'ERROR', 'description' => 'API down']],
        RawResponse::fake(status: 503),
    ));

    $items = iterator_to_array($connector->all('/v3/payments', []));

    expect($items)->toHaveCount(1);
    expect($items[0])->toBeInstanceOf(AsaasPaginatedError::class);
    expect($items[0]->errors[0]['description'])->toBe('API down');
    expect($items[0]->offset)->toBe(0);
    expect($items[0]->limit)->toBe(100);
});

it('all uses default limit of 100', function (): void {
    $capturedQuery = [];
    $connector = fakeConnector(function (string $path, array $query) use (&$capturedQuery): AsaasResult {
        $capturedQuery = $query;

        return AsaasResult::success(
            ['data' => [['id' => 'p1']], 'totalCount' => 1, 'hasMore' => false, 'limit' => 100, 'offset' => 0],
            RawResponse::fake(),
        );
    });

    iterator_to_array($connector->all('/v3/payments', []));

    expect($capturedQuery['limit'])->toBe(100);
    expect($capturedQuery['offset'])->toBe(0);
});

it('all enforces minimum limit of 1', function (): void {
    $capturedQuery = [];
    $connector = fakeConnector(function (string $path, array $query) use (&$capturedQuery): AsaasResult {
        $capturedQuery = $query;

        return AsaasResult::success(
            ['data' => [['id' => 'p1']], 'totalCount' => 1, 'hasMore' => false, 'limit' => 1, 'offset' => 0],
            RawResponse::fake(),
        );
    });

    iterator_to_array($connector->all('/v3/payments', ['limit' => 0]));

    expect($capturedQuery['limit'])->toBe(1);
});

it('all stops when data is empty', function (): void {
    $connector = fakeConnector(fn (): AsaasResult => AsaasResult::success(
        ['data' => [], 'totalCount' => 0, 'hasMore' => true, 'limit' => 10, 'offset' => 0],
        RawResponse::fake(),
    ));

    $items = iterator_to_array($connector->all('/v3/payments', []));

    expect($items)->toBeEmpty();
});
