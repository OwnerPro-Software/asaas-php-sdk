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

it('UpdatePaymentRequest: explicit null is kept in toArray', function (): void {
    $request = new UpdatePaymentRequest(description: null, value: 200.00);

    expect($request->description)->toBeNull();
    $array = $request->toArray();
    expect($array)->toHaveKey('description');
    expect($array['description'])->toBeNull();
    expect($array['value'])->toBe(200.00);
});

it('UpdatePaymentRequest: fromArray with missing key defaults to Missing', function (): void {
    $request = UpdatePaymentRequest::fromArray(['value' => 100.00]);

    expect($request->description)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['value' => 100.00]);
});

it('UpdatePaymentRequest: fromArray with explicit null keeps null', function (): void {
    $request = UpdatePaymentRequest::fromArray(['value' => 100.00, 'description' => null]);

    expect($request->description)->toBeNull();
    expect($request->toArray())->toBe(['value' => 100.00, 'description' => null]);
});

it('UpdatePaymentRequest: fromArray serializes nested Split DTOs', function (): void {
    $request = UpdatePaymentRequest::fromArray([
        'value' => 200.00,
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 20.00]],
    ]);

    expect($request->split)->toBeArray();
    expect($request->split[0])->toBeInstanceOf(Split::class);
    expect($request->toArray())->toBe([
        'value' => 200.00,
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 20.00]],
    ]);
});

// --- UpdateInvoiceRequest ---

it('UpdateInvoiceRequest: unprovided fields default to Missing and are stripped', function (): void {
    $request = new UpdateInvoiceRequest(value: 500.00);

    expect($request->serviceDescription)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['value' => 500.00]);
});

it('UpdateInvoiceRequest: explicit null is kept in toArray', function (): void {
    $request = new UpdateInvoiceRequest(observations: null, value: 500.00);

    $array = $request->toArray();
    expect($array)->toHaveKey('observations');
    expect($array['observations'])->toBeNull();
});

it('UpdateInvoiceRequest: fromArray with explicit null keeps null', function (): void {
    $request = UpdateInvoiceRequest::fromArray(['value' => 500.00, 'externalReference' => null]);

    expect($request->externalReference)->toBeNull();
    expect($request->toArray())->toBe(['value' => 500.00, 'externalReference' => null]);
});

it('UpdateInvoiceRequest: fromArray serializes nested Taxes DTO', function (): void {
    $request = UpdateInvoiceRequest::fromArray([
        'value' => 600.00,
        'taxes' => ['retainIss' => false, 'iss' => 3.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5],
    ]);

    expect($request->taxes)->toBeInstanceOf(Taxes::class);
    expect($request->toArray()['taxes'])->toBe(['retainIss' => false, 'iss' => 3.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
});

// --- UpdateWebhookRequest ---

it('UpdateWebhookRequest: unprovided fields default to Missing and are stripped', function (): void {
    $request = new UpdateWebhookRequest(enabled: false);

    expect($request->url)->toBe(Missing::Value);
    expect($request->toArray())->toBe(['enabled' => false]);
});

it('UpdateWebhookRequest: explicit null is kept in toArray', function (): void {
    $request = new UpdateWebhookRequest(name: null, enabled: true);

    $array = $request->toArray();
    expect($array)->toHaveKey('name');
    expect($array['name'])->toBeNull();
});

it('UpdateWebhookRequest: fromArray with explicit null keeps null', function (): void {
    $request = UpdateWebhookRequest::fromArray(['enabled' => true, 'name' => null]);

    expect($request->name)->toBeNull();
    expect($request->toArray())->toBe(['name' => null, 'enabled' => true]);
});

it('UpdateWebhookRequest: debugInfo shows null for null authToken and omits Missing fields', function (): void {
    $request = new UpdateWebhookRequest(url: 'https://example.com', authToken: null);

    $debug = $request->__debugInfo();
    expect($debug['url'])->toBe('https://example.com');
    expect($debug['authToken'])->toBeNull();
    expect($debug)->not->toHaveKey('name');
});
