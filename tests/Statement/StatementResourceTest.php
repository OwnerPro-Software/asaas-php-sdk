<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Statement\Response\StatementResponse;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;

mutates(StatementResource::class);

function statementConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function statementResource(): StatementResource
{
    return new StatementResource(statementConnector());
}

it('lists financial transactions', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list', 'hasMore' => true, 'totalCount' => 100, 'limit' => 10, 'offset' => 0,
        'data' => [
            ['id' => 'ft_1', 'type' => 'PAYMENT_RECEIVED', 'value' => 150.00, 'balance' => 1500.00, 'date' => '2026-03-25', 'description' => 'Payment cus_1'],
            ['id' => 'ft_2', 'type' => 'TRANSFER', 'value' => -50.00, 'balance' => 1450.00, 'date' => '2026-03-25', 'description' => 'Transfer'],
        ],
    ], 200)]);

    $result = statementResource()->list(['startDate' => '2026-03-01', 'finishDate' => '2026-03-31']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0])->toBeInstanceOf(StatementResponse::class);
    expect($result->data[0]->type)->toBe('PAYMENT_RECEIVED');
    expect($result->totalCount)->toBe(100);
    expect($result->hasMore)->toBeTrue();

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/financialTransactions'));
});

it('iterates all transactions lazily', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 2, 'offset' => 0,
        'data' => [
            ['id' => 'ft_1', 'type' => 'PAYMENT_RECEIVED', 'value' => 100],
            ['id' => 'ft_2', 'type' => 'TRANSFER', 'value' => -50],
        ],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 3, 'limit' => 2, 'offset' => 2,
        'data' => [
            ['id' => 'ft_3', 'type' => 'PAYMENT_RECEIVED', 'value' => 200],
        ],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $items = iterator_to_array(statementResource()->all(['limit' => 2]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeInstanceOf(StatementResponse::class);
    expect($items[2]->id)->toBe('ft_3');
});

it('returns failure on error', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'Unauthorized']]], 401)]);

    $result = statementResource()->list();

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(401);
});
