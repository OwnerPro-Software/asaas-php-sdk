<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\PaginationEnvelope;

mutates(PaginationEnvelope::class);

function paginateBody(array $body): void
{
    Http::fake(['*' => Http::response($body, 200)]);

    AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->paginate('/payments', []);
}

it('reads a canonical envelope', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list', 'hasMore' => true, 'totalCount' => 7, 'limit' => 2, 'offset' => 0,
        'data' => [['id' => 'pay_1'], ['id' => 'pay_2']],
    ], 200)]);

    $page = AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->paginate('/payments', []);

    expect($page->success)->toBeTrue();
    expect($page->data)->toBe([['id' => 'pay_1'], ['id' => 'pay_2']]);
    expect($page->totalCount)->toBe(7);
    expect($page->hasMore)->toBeTrue();
    expect($page->limit)->toBe(2);
});

it('reads an envelope that omits every pagination key', function (): void {
    Http::fake(['*' => Http::response(['data' => [['id' => 'pay_1']]], 200)]);

    $page = AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->paginate('/payments', []);

    expect($page->data)->toBe([['id' => 'pay_1']]);
    expect($page->totalCount)->toBe(0);
    expect($page->hasMore)->toBeFalse();
    expect($page->limit)->toBe(0);
});

it('reads an envelope with no data key as an empty page', function (): void {
    Http::fake(['*' => Http::response(['object' => 'list', 'totalCount' => 0], 200)]);

    $page = AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->paginate('/payments', []);

    expect($page->data)->toBe([]);
});

it('keeps a row that carries no fields', function (): void {
    Http::fake(['*' => Http::response(['data' => [[]]], 200)]);

    $page = AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->paginate('/payments', []);

    expect($page->data)->toBe([[]]);
});

/**
 * Each of these once raised a `TypeError` out of the middle of `paginate()`,
 * escaping the Result contract entirely. They are indeterminate rather than
 * failures: a 2xx arrived, and the SDK cannot tell how much of the set it holds.
 */
it('refuses an envelope field the walk cannot read', function (array $body): void {
    paginateBody($body);
})
    ->throws(IndeterminateResultException::class)
    ->with([
        'data as a scalar' => [['data' => 'oops']],
        'data as an object' => [['data' => ['id' => 'pay_1']]],
        'a scalar row' => [['data' => ['pay_1']]],
        'a list-shaped row' => [['data' => [[1, 2]]]],
        'totalCount as a numeric string' => [['data' => [], 'totalCount' => '3']],
        'totalCount as a float' => [['data' => [], 'totalCount' => 3.5]],
        'limit as a numeric string' => [['data' => [], 'limit' => '10']],
        'hasMore as a string' => [['data' => [], 'hasMore' => 'true']],
        'hasMore as an int' => [['data' => [], 'hasMore' => 1]],
    ]);

it('reports the unreadable envelope as a received 2xx', function (): void {
    Http::fake(['*' => Http::response(['data' => 'oops'], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);

    try {
        $connector->paginate('/payments', []);
    } catch (IndeterminateResultException $e) {
        expect($e->phase)->toBe('body');
        expect($e->response?->status())->toBe(200);

        return;
    }

    throw new RuntimeException('paginate() did not refuse the envelope');
});
