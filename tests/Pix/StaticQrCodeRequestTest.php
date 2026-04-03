<?php

declare(strict_types=1);

use OwnerPro\Asaas\Pix\Request\StaticQrCodeRequest;

mutates(StaticQrCodeRequest::class);

it('creates from array with all fields', function (): void {
    $request = StaticQrCodeRequest::fromArray([
        'addressKey' => 'pix_key_123',
        'description' => 'Test QR',
        'value' => 50.00,
        'format' => 'ALL',
        'expirationDate' => '2026-12-31T23:59:59Z',
        'expirationSeconds' => 3600,
        'allowsMultiplePayments' => true,
        'externalReference' => 'ref_1',
    ]);

    expect($request->addressKey)->toBe('pix_key_123');
    expect($request->description)->toBe('Test QR');
    expect($request->value)->toBe(50.00);
    expect($request->format)->toBe('ALL');
    expect($request->expirationDate)->toBe('2026-12-31T23:59:59Z');
    expect($request->expirationSeconds)->toBe(3600);
    expect($request->allowsMultiplePayments)->toBeTrue();
    expect($request->externalReference)->toBe('ref_1');
});

it('creates from empty array', function (): void {
    $request = StaticQrCodeRequest::fromArray([]);

    expect($request->addressKey)->toBeNull();
});

it('converts to array filtering nulls', function (): void {
    $request = new StaticQrCodeRequest(addressKey: 'pix_key_123', value: 50.00);

    expect($request->toArray())->toBe(['addressKey' => 'pix_key_123', 'value' => 50.00]);
});
