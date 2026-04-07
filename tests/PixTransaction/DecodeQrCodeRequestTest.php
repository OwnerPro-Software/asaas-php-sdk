<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\Request\DecodeQrCodeRequest;

mutates(DecodeQrCodeRequest::class);

it('creates from array with all fields', function (): void {
    $request = DecodeQrCodeRequest::fromArray([
        'payload' => '00020126...',
        'changeValue' => 10.50,
        'expectedPaymentDate' => '2026-04-01',
    ]);

    expect($request->payload)->toBe('00020126...');
    expect($request->changeValue)->toBe(10.50);
    expect($request->expectedPaymentDate)->toBe('2026-04-01');
});

it('converts to array filtering nulls', function (): void {
    $request = new DecodeQrCodeRequest(payload: '00020126...');

    expect($request->toArray())->toBe(['payload' => '00020126...']);
});

it('throws when payload is missing', function (): void {
    DecodeQrCodeRequest::fromArray([]);
})->throws(InvalidArgumentException::class);
