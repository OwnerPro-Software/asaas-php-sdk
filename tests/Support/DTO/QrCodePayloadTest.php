<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\QrCodePayload;

mutates(QrCodePayload::class);

it('creates from array with all fields', function (): void {
    $qr = QrCodePayload::fromArray(['payload' => '00020126...', 'changeValue' => 10.50]);

    expect($qr->payload)->toBe('00020126...');
    expect($qr->changeValue)->toBe(10.50);
});

it('converts to array filtering nulls', function (): void {
    $qr = new QrCodePayload(payload: '00020126...');

    expect($qr->toArray())->toBe(['payload' => '00020126...']);
});

it('throws when payload is missing', function (): void {
    QrCodePayload::fromArray([]);
})->throws(InvalidArgumentException::class, "Field 'payload' is required.");
