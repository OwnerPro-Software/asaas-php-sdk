<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticFrequency;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticResource;
use OwnerPro\Asaas\PixAutomatic\Request\AuthorizationRequest;
use OwnerPro\Asaas\PixAutomatic\Request\ImmediateQrCode;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;

mutates(PixAutomaticResource::class);

function pixAutoConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function pixAutoResource(): PixAutomaticResource
{
    return new PixAutomaticResource(pixAutoConnector());
}

it('creates an authorization from array', function (): void {
    Http::fake(['*' => Http::response(['id' => 'auth_1', 'status' => 'CREATED'], 200)]);

    $result = pixAutoResource()->createAuthorization([
        'frequency' => PixAutomaticFrequency::Monthly,
        'contractId' => 'CONTRACT-1',
        'startDate' => '2026-01-01',
        'customerId' => 'cus_001',
        'immediateQrCode' => ['expirationSeconds' => 3600, 'originalValue' => 100.0],
    ]);

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('auth_1');

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $request->url() === 'https://api-sandbox.asaas.com/v3/pix/automatic/authorizations'
            && $request->method() === 'POST'
            && $body['frequency'] === 'MONTHLY'
            && $body['contractId'] === 'CONTRACT-1'
            && $body['immediateQrCode'] === ['expirationSeconds' => 3600, 'originalValue' => 100.0];
    });
});

it('creates an authorization from request object', function (): void {
    Http::fake(['*' => Http::response(['id' => 'auth_1', 'status' => 'CREATED'], 200)]);

    $result = pixAutoResource()->createAuthorization(new AuthorizationRequest(
        frequency: PixAutomaticFrequency::Monthly,
        contractId: 'CONTRACT-1',
        startDate: '2026-01-01',
        customerId: 'cus_001',
        immediateQrCode: new ImmediateQrCode(expirationSeconds: 3600, originalValue: 100.0),
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/automatic/authorizations'
        && $request->method() === 'POST');
});

it('lists authorizations', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0,
        'data' => [['id' => 'auth_1', 'status' => 'ACTIVE']],
    ], 200)]);

    $result = pixAutoResource()->listAuthorizations(['status' => 'ACTIVE']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/pix/automatic/authorizations'));
});

it('finds an authorization', function (): void {
    Http::fake(['*' => Http::response(['id' => 'auth_1', 'status' => 'ACTIVE'], 200)]);

    $result = pixAutoResource()->findAuthorization('auth_1');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('auth_1');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/automatic/authorizations/auth_1'
        && $request->method() === 'GET');
});

it('cancels an authorization via DELETE', function (): void {
    Http::fake(['*' => Http::response(['id' => 'auth_1', 'status' => 'CANCELLED'], 200)]);

    $result = pixAutoResource()->cancelAuthorization('auth_1');

    expect($result->success)->toBeTrue();
    expect($result->data['status'])->toBe('CANCELLED');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/automatic/authorizations/auth_1'
        && $request->method() === 'DELETE');
});

it('lists payment instructions', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0,
        'data' => [['id' => 'pi_1', 'status' => 'SCHEDULED']],
    ], 200)]);

    $result = pixAutoResource()->listPaymentInstructions(['status' => 'SCHEDULED']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/pix/automatic/paymentInstructions'));
});

it('finds a payment instruction', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pi_1', 'status' => 'SCHEDULED'], 200)]);

    $result = pixAutoResource()->findPaymentInstruction('pi_1');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pi_1');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/pix/automatic/paymentInstructions/pi_1'
        && $request->method() === 'GET');
});

it('iterates all authorizations lazily', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 2, 'limit' => 1, 'offset' => 0,
        'data' => [['id' => 'auth_1']],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 2, 'limit' => 1, 'offset' => 1,
        'data' => [['id' => 'auth_2']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $items = iterator_to_array(pixAutoResource()->allAuthorizations(['limit' => 1]));

    expect($items)->toHaveCount(2);
    expect($items[1]['id'])->toBe('auth_2');
});

it('iterates all payment instructions lazily', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 2, 'limit' => 1, 'offset' => 0,
        'data' => [['id' => 'pi_1']],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 2, 'limit' => 1, 'offset' => 1,
        'data' => [['id' => 'pi_2']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $items = iterator_to_array(pixAutoResource()->allPaymentInstructions(['limit' => 1]));

    expect($items)->toHaveCount(2);
    expect($items[0]['id'])->toBe('pi_1');
});

it('returns failure on API error when creating authorization', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = pixAutoResource()->createAuthorization([
        'frequency' => 'MONTHLY',
        'contractId' => 'X',
        'startDate' => '2026-01-01',
        'customerId' => 'c',
        'immediateQrCode' => ['expirationSeconds' => 1, 'originalValue' => 1.0],
    ]);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
})->with('error_fixture');

it('rejects empty id on findAuthorization', function (): void {
    pixAutoResource()->findAuthorization('');
})->throws(InvalidArgumentException::class);

it('rejects empty id on cancelAuthorization', function (): void {
    pixAutoResource()->cancelAuthorization('');
})->throws(InvalidArgumentException::class);

it('rejects empty id on findPaymentInstruction', function (): void {
    pixAutoResource()->findPaymentInstruction('');
})->throws(InvalidArgumentException::class);
