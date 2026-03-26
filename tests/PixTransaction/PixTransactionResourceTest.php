<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\PixTransaction\Response\DecodedQrCodeResponse;
use OwnerPro\Asaas\PixTransaction\Response\PixTransactionResponse;
use OwnerPro\Asaas\Support\AsaasConnector;

mutates(PixTransactionResource::class);

function pixTxConnector(): AsaasConnector
{
    return new AsaasConnector('test-key', 'sandbox', 30);
}

function pixTxResource(): PixTransactionResource
{
    return new PixTransactionResource(pixTxConnector());
}

dataset('pix_tx_fixture', [fn (): array => [
    'id' => 'tx_1', 'status' => 'DONE', 'value' => 50.00,
    'endToEndIdentifier' => 'E123', 'finality' => 'PAYMENT',
]]);

dataset('pix_tx_list_fixture', [fn (): array => [
    'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 10, 'offset' => 0,
    'data' => [['id' => 'tx_1', 'status' => 'DONE', 'value' => 50.00],
        ['id' => 'tx_2', 'status' => 'DONE', 'value' => 75.00]],
]]);

it('decodes a qr code', function (): void {
    Http::fake(['*' => Http::response([
        'payload' => '00020126...', 'type' => 'STATIC', 'value' => 50.00,
    ], 200)]);

    $result = pixTxResource()->decodeQrCode(['payload' => '00020126...']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(DecodedQrCodeResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/qrCodes/decode'
        && $request->method() === 'POST');
});

it('pays a qr code', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixTxResource()->payQrCode(['qrCode' => ['payload' => '00020126...'], 'value' => 50.00]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PixTransactionResponse::class);
    expect($result->data->id)->toBe('tx_1');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/qrCodes/pay'
        && $request->method() === 'POST');
})->with('pix_tx_fixture');

it('lists pix transactions', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixTxResource()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0])->toBeInstanceOf(PixTransactionResponse::class);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/pix/transactions'));
})->with('pix_tx_list_fixture');

it('finds a pix transaction', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixTxResource()->find('tx_1');

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('tx_1');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/transactions/tx_1');
})->with('pix_tx_fixture');

it('cancels a pix transaction', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'tx_1', 'status' => 'CANCELLED', 'value' => 50.00,
    ], 200)]);

    $result = pixTxResource()->cancel('tx_1');

    expect($result->success)->toBeTrue();
    expect($result->data->status)->toBe('CANCELLED');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/transactions/tx_1/cancel'
        && $request->method() === 'POST');
});

it('iterates all pix transactions lazily', function (array $page1): void {
    $page2 = [
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 3,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'tx_3', 'status' => 'DONE', 'value' => 100.00]],
    ];

    Http::fakeSequence()
        ->push($page1, 200)
        ->push($page2, 200);

    $items = iterator_to_array(pixTxResource()->all(['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeInstanceOf(PixTransactionResponse::class);
    expect($items[2]->id)->toBe('tx_3');
})->with('pix_tx_list_fixture');

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = pixTxResource()->decodeQrCode(['payload' => 'invalid']);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
