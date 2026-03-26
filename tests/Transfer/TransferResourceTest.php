<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Transfer\Request\CreateTransferRequest;
use OwnerPro\Asaas\Transfer\Response\TransferResponse;
use OwnerPro\Asaas\Transfer\TransferResource;

mutates(TransferResource::class);

function transferConnector(): AsaasConnector
{
    return new AsaasConnector('test-key', 'sandbox', 30);
}

function transferResource(): TransferResource
{
    return new TransferResource(transferConnector());
}

dataset('transfer_fixture', [fn (): array => [
    'id' => 'tr_123', 'type' => 'PIX', 'dateCreated' => '2026-03-25',
    'value' => 100.00, 'netValue' => 100.00, 'status' => 'PENDING',
    'transferFee' => 0.00, 'authorized' => true,
]]);

dataset('transfer_list_fixture', [fn (): array => [
    'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 10, 'offset' => 0,
    'data' => [['id' => 'tr_1', 'value' => 50.00, 'status' => 'DONE'],
        ['id' => 'tr_2', 'value' => 75.00, 'status' => 'DONE']],
]]);

it('creates a transfer from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = transferResource()->create(['value' => 100.00, 'pixAddressKey' => 'email@test.com']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(TransferResponse::class);
    expect($result->data->id)->toBe('tr_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/transfers'
        && $request->method() === 'POST');
})->with('transfer_fixture');

it('creates a transfer from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = transferResource()->create(new CreateTransferRequest(value: 100.00, pixAddressKey: 'email@test.com'));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/transfers'
        && $request->method() === 'POST');
})->with('transfer_fixture');

it('validates value is required', function (): void {
    transferResource()->create([]);
})->throws(InvalidArgumentException::class, "Field 'value' is required.");

it('lists transfers', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = transferResource()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/transfers'));
})->with('transfer_list_fixture');

it('finds a transfer', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = transferResource()->find('tr_123');

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('tr_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/transfers/tr_123');
})->with('transfer_fixture');

it('cancels a transfer', function (array $fixture): void {
    $cancelled = array_merge($fixture, ['status' => 'CANCELLED']);
    Http::fake(['*' => Http::response($cancelled, 200)]);

    $result = transferResource()->cancel('tr_123');

    expect($result->success)->toBeTrue();
    expect($result->data->status)->toBe('CANCELLED');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/transfers/tr_123/cancel'
        && $request->method() === 'DELETE');
})->with('transfer_fixture');

it('iterates all transfers lazily', function (array $page1): void {
    $page2 = [
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 3,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'tr_3', 'value' => 200.00, 'status' => 'DONE']],
    ];

    Http::fakeSequence()
        ->push($page1, 200)
        ->push($page2, 200);

    $items = iterator_to_array(transferResource()->all(['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeInstanceOf(TransferResponse::class);
    expect($items[2]->id)->toBe('tr_3');
})->with('transfer_list_fixture');

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = transferResource()->create(['value' => 0]);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
