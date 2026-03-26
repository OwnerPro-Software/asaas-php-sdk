<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest;
use OwnerPro\Asaas\Support\DTO\QrCodePayload;

mutates(PayQrCodeRequest::class);

it('creates from array with all fields', function (): void {
    $request = PayQrCodeRequest::fromArray([
        'qrCode' => ['payload' => '00020126...'],
        'value' => 100.00,
        'description' => 'QR payment',
        'scheduleDate' => '2026-04-01',
    ]);

    expect($request->qrCode)->toBe(['payload' => '00020126...']);
    expect($request->value)->toBe(100.00);
    expect($request->description)->toBe('QR payment');
});

it('converts to array filtering nulls', function (): void {
    $request = new PayQrCodeRequest(
        qrCode: ['payload' => '00020126...'],
        value: 100.00,
    );

    expect($request->toArray())->toBe([
        'qrCode' => ['payload' => '00020126...'],
        'value' => 100.00,
    ]);
});

it('serializes nested QrCodePayload DTO in toArray', function (): void {
    $request = new PayQrCodeRequest(
        qrCode: new QrCodePayload(payload: '00020126...', changeValue: 5.00),
        value: 100.00,
    );

    expect($request->toArray())->toBe([
        'qrCode' => ['payload' => '00020126...', 'changeValue' => 5.00],
        'value' => 100.00,
    ]);
});

it('throws when qrCode is missing', function (): void {
    PayQrCodeRequest::fromArray(['value' => 100.00]);
})->throws(InvalidArgumentException::class, "Field 'qrCode' is required.");

it('throws when value is missing', function (): void {
    PayQrCodeRequest::fromArray(['qrCode' => ['payload' => '00020126...']]);
})->throws(InvalidArgumentException::class, "Field 'value' is required.");
