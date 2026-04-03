<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\BillPayment\Request\CreateBillPaymentRequest;
use OwnerPro\Asaas\BillPayment\Request\SimulateBillPaymentRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;

mutates(BillPaymentResource::class);

function billConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function billPaymentResource(): BillPaymentResource
{
    return new BillPaymentResource(billConnector());
}

dataset('bill_fixture', [fn (): array => [
    'id' => 'bill_123', 'status' => 'PENDING', 'value' => 250.00,
    'identificationField' => '12345.67890 12345.678901 12345.678901 1 12340000025000',
    'fee' => 1.50, 'canBeCancelled' => true,
]]);

it('creates a bill payment from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = billPaymentResource()->create(['identificationField' => '12345.67890...']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();
    expect($result->data['id'])->toBe('bill_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/bill'
        && $request->method() === 'POST');
})->with('bill_fixture');

it('creates a bill payment from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = billPaymentResource()->create(new CreateBillPaymentRequest(identificationField: '12345.67890...'));

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('bill_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/bill'
        && $request->method() === 'POST');
})->with('bill_fixture');

it('validates identificationField is required', function (): void {
    billPaymentResource()->create([]);
})->throws(TypeError::class);

it('lists bill payments', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0,
        'data' => [['id' => 'bill_1', 'status' => 'PAID', 'value' => 100]],
    ], 200)]);

    $result = billPaymentResource()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/bill'));
});

it('finds a bill payment', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = billPaymentResource()->find('bill_123');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('bill_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/bill/bill_123');
})->with('bill_fixture');

it('simulates a bill payment', function (): void {
    Http::fake(['*' => Http::response([
        'minimumScheduleDate' => '2026-03-26', 'fee' => 1.50,
        'bankSlipInfo' => ['dueDate' => '2026-04-01', 'value' => 250.00],
    ], 200)]);

    $result = billPaymentResource()->simulate(['identificationField' => '12345...']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();
    expect($result->data['fee'])->toBe(1.50);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/bill/simulate'
        && $request->method() === 'POST');
});

it('cancels a bill payment', function (array $fixture): void {
    $cancelled = array_merge($fixture, ['status' => 'CANCELLED']);
    Http::fake(['*' => Http::response($cancelled, 200)]);

    $result = billPaymentResource()->cancel('bill_123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/bill/bill_123/cancel'
        && $request->method() === 'POST');
})->with('bill_fixture');

it('iterates all bill payments lazily', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 2, 'offset' => 0,
        'data' => [['id' => 'bill_1', 'status' => 'PAID'], ['id' => 'bill_2', 'status' => 'PENDING']],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 3, 'limit' => 2, 'offset' => 2,
        'data' => [['id' => 'bill_3', 'status' => 'PAID']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $items = iterator_to_array(billPaymentResource()->all(['limit' => 2]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeArray();
    expect($items[2]['id'])->toBe('bill_3');
});

it('simulates a bill payment from request object', function (): void {
    Http::fake(['*' => Http::response([
        'minimumScheduleDate' => '2026-03-26', 'fee' => 1.50,
        'bankSlipInfo' => ['dueDate' => '2026-04-01', 'value' => 250.00],
    ], 200)]);

    $result = billPaymentResource()->simulate(new SimulateBillPaymentRequest(
        identificationField: '12345...',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/bill/simulate'
        && $request->method() === 'POST');
});

it('returns failure on API error', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'Invalid bill']]], 400)]);

    $result = billPaymentResource()->create(['identificationField' => 'invalid']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
});
