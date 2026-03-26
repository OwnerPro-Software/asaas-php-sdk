<?php

declare(strict_types=1);

use OwnerPro\Asaas\Pix\Request\CreateStaticQrCodeRequest;

mutates(CreateStaticQrCodeRequest::class);

it('creates from array with all fields', function (): void {
    $request = CreateStaticQrCodeRequest::fromArray([
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
    expect($request->format)->toBe('ALL');
    expect($request->allowsMultiplePayments)->toBeTrue();
});

it('creates from empty array', function (): void {
    $request = CreateStaticQrCodeRequest::fromArray([]);

    expect($request->addressKey)->toBeNull();
});

it('converts to array filtering nulls', function (): void {
    $request = new CreateStaticQrCodeRequest(addressKey: 'pix_key_123', value: 50.00);

    expect($request->toArray())->toBe(['addressKey' => 'pix_key_123', 'value' => 50.00]);
});
