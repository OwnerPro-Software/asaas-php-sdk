<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\Pix\Request\CreatePixKeyRequest;
use OwnerPro\Asaas\Pix\Request\CreateStaticQrCodeRequest;
use OwnerPro\Asaas\Pix\Response\PixResponse;
use OwnerPro\Asaas\Pix\Response\StaticQrCodeResponse;
use OwnerPro\Asaas\Pix\Response\TokenBucketResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\DeletedResponse;

mutates(PixResource::class);

function pixConnector(): AsaasConnector
{
    return new AsaasConnector('test-key', 'sandbox', 30);
}

function pixResource(): PixResource
{
    return new PixResource(pixConnector());
}

dataset('pix_key_fixture', [fn (): array => [
    'id' => 'pix_123', 'key' => 'random-key', 'type' => 'EVP',
    'status' => 'ACTIVE', 'dateCreated' => '2026-03-25',
    'canBeDeleted' => true, 'cannotBeDeletedReason' => null, 'qrCode' => null,
]]);

dataset('pix_key_list_fixture', [fn (): array => [
    'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 10, 'offset' => 0,
    'data' => [['id' => 'pix_1', 'key' => 'k1', 'type' => 'EVP', 'status' => 'ACTIVE'],
        ['id' => 'pix_2', 'key' => 'k2', 'type' => 'EVP', 'status' => 'ACTIVE']],
]]);

// --- createKey ---

it('creates a pix key from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixResource()->createKey(['type' => 'EVP']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PixResponse::class);
    expect($result->data->id)->toBe('pix_123');
    expect($result->data->type)->toBe('EVP');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/addressKeys'
        && $request->method() === 'POST');
})->with('pix_key_fixture');

it('creates a pix key from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixResource()->createKey(new CreatePixKeyRequest(type: 'EVP'));

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pix_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/addressKeys'
        && $request->method() === 'POST');
})->with('pix_key_fixture');

it('validates required type field', function (): void {
    pixResource()->createKey([]);
})->throws(InvalidArgumentException::class, "Field 'type' is required.");

// --- listKeys ---

it('lists pix keys', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixResource()->listKeys();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/pix/addressKeys'));
})->with('pix_key_list_fixture');

// --- findKey ---

it('finds a pix key', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = pixResource()->findKey('pix_123');

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pix_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/addressKeys/pix_123');
})->with('pix_key_fixture');

// --- deleteKey ---

it('deletes a pix key', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pix_123', 'key' => 'x', 'type' => 'EVP', 'status' => 'DELETED'], 200)]);

    $result = pixResource()->deleteKey('pix_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PixResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/addressKeys/pix_123'
        && $request->method() === 'DELETE');
});

// --- createStaticQrCode ---

it('creates a static qr code', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'qr_1', 'encodedImage' => 'base64...', 'payload' => '00020126...',
    ], 200)]);

    $result = pixResource()->createStaticQrCode(['description' => 'Test']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(StaticQrCodeResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/qrCodes/static'
        && $request->method() === 'POST');
});

// --- deleteStaticQrCode ---

it('deletes a static qr code', function (): void {
    Http::fake(['*' => Http::response(['id' => 'qr_1', 'deleted' => true], 200)]);

    $result = pixResource()->deleteStaticQrCode('qr_1');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(DeletedResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/qrCodes/static/qr_1'
        && $request->method() === 'DELETE');
});

// --- tokenBucket ---

it('checks token bucket', function (): void {
    Http::fake(['*' => Http::response(['capacity' => 10, 'remaining' => 7], 200)]);

    $result = pixResource()->tokenBucket();

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(TokenBucketResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/tokenBucket/addressKey');
});

// --- all (lazy iterator) ---

it('iterates all pix keys lazily', function (array $page1): void {
    $page2 = [
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 3,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'pix_3', 'key' => 'k3', 'type' => 'EVP', 'status' => 'ACTIVE']],
    ];

    Http::fakeSequence()
        ->push($page1, 200)
        ->push($page2, 200);

    $items = iterator_to_array(pixResource()->all(['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeInstanceOf(PixResponse::class);
    expect($items[2]->id)->toBe('pix_3');
})->with('pix_key_list_fixture');

// --- createStaticQrCode from request object ---

it('creates static qr code from request object', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'qr_123', 'encodedImage' => 'base64...', 'payload' => '00020126...',
    ], 200)]);

    $result = pixResource()->createStaticQrCode(new CreateStaticQrCodeRequest(
        addressKey: 'pix_key_123',
        value: 50.00,
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/qrCodes/static'
        && $request->method() === 'POST'
        && $request->data()['addressKey'] === 'pix_key_123');
});

// --- error handling ---

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = pixResource()->createKey(['type' => 'EVP']);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
