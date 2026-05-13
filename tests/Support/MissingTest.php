<?php

declare(strict_types=1);

use OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\Missing;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;

mutates(Missing::class);

// --- UpdatePaymentRequest ---

it('UpdatePaymentRequest: unprovided fields default to Missing and are stripped', function (): void {
    $request = new UpdatePaymentRequest(value: 200.00);

    expect($request->description)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['value' => 200.00]);
});

it('UpdatePaymentRequest: fromArray with missing key defaults to Missing', function (): void {
    $request = UpdatePaymentRequest::fromArray(['value' => 100.00]);

    expect($request->description)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['value' => 100.00]);
});

it('UpdatePaymentRequest: fromArray serializes nested Split DTOs', function (): void {
    $request = UpdatePaymentRequest::fromArray([
        'value' => 200.00,
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 20.00]],
    ]);

    expect($request->split)->toBeArray();
    expect($request->split[0])->toBeInstanceOf(Split::class);
    expect($request->toArray())->toBe([
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 20.00]],
        'value' => 200.00,
    ]);
});

it('UpdatePaymentRequest: explicit null on a typed field is rejected at runtime', function (): void {
    // @phpstan-ignore-next-line — wire test pin: type system rejects null; runtime confirms
    new UpdatePaymentRequest(description: null, value: 200.00);
})->throws(TypeError::class);

// --- UpdateInvoiceRequest ---

it('UpdateInvoiceRequest: unprovided fields default to Missing and are stripped', function (): void {
    $request = new UpdateInvoiceRequest(value: 500.00);

    expect($request->serviceDescription)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['value' => 500.00]);
});

it('UpdateInvoiceRequest: fromArray serializes nested Taxes DTO', function (): void {
    $request = UpdateInvoiceRequest::fromArray([
        'value' => 600.00,
        'taxes' => ['retainIss' => false, 'iss' => 3.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5],
    ]);

    expect($request->taxes)->toBeInstanceOf(Taxes::class);
    expect($request->toArray()['taxes'])->toBe(['retainIss' => false, 'iss' => 3.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
});

it('UpdateInvoiceRequest: explicit null on a typed field is rejected at runtime', function (): void {
    // @phpstan-ignore-next-line — wire test pin
    new UpdateInvoiceRequest(observations: null, value: 500.00);
})->throws(TypeError::class);

// --- UpdateWebhookRequest ---

it('UpdateWebhookRequest: unprovided fields default to Missing and are stripped', function (): void {
    $request = new UpdateWebhookRequest(enabled: false);

    expect($request->url)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['enabled' => false]);
});

it('UpdateWebhookRequest: explicit null on a typed field is rejected at runtime', function (): void {
    // @phpstan-ignore-next-line — wire test pin
    new UpdateWebhookRequest(name: null, enabled: true);
})->throws(TypeError::class);

it('UpdateWebhookRequest: debugInfo omits Missing fields', function (): void {
    $request = new UpdateWebhookRequest(url: 'https://example.com');

    $debug = $request->__debugInfo();
    expect($debug['url'])->toBe('https://example.com');
    expect($debug)->not->toHaveKey('name');
    expect($debug)->not->toHaveKey('authToken');
});
