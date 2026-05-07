<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixAutomatic\Request\ImmediateQrCode;

mutates(ImmediateQrCode::class);

it('creates from array with required fields', function (): void {
    $dto = ImmediateQrCode::fromArray(['expirationSeconds' => 3600, 'originalValue' => 100.0]);

    expect($dto->expirationSeconds)->toBe(3600);
    expect($dto->originalValue)->toBe(100.0);
    expect($dto->pixKey)->toBeNull();
    expect($dto->description)->toBeNull();
});

it('creates from array with all fields', function (): void {
    $dto = ImmediateQrCode::fromArray([
        'expirationSeconds' => 7200,
        'originalValue' => 50.0,
        'pixKey' => 'b6295ee1-f054-47d1-9e90-ee57b74f60d9',
        'description' => 'first charge',
    ]);

    expect($dto->expirationSeconds)->toBe(7200);
    expect($dto->originalValue)->toBe(50.0);
    expect($dto->pixKey)->toBe('b6295ee1-f054-47d1-9e90-ee57b74f60d9');
    expect($dto->description)->toBe('first charge');
});

it('throws when expirationSeconds is missing', function (): void {
    ImmediateQrCode::fromArray(['originalValue' => 100.0]);
})->throws(InvalidArgumentException::class, 'ImmediateQrCode: expirationSeconds is required');

it('throws when originalValue is missing', function (): void {
    ImmediateQrCode::fromArray(['expirationSeconds' => 3600]);
})->throws(InvalidArgumentException::class, 'ImmediateQrCode: originalValue is required');

it('serializes to array via toArray', function (): void {
    $dto = new ImmediateQrCode(expirationSeconds: 3600, originalValue: 100.0, pixKey: 'k', description: 'd');

    expect($dto->toArray())->toBe([
        'expirationSeconds' => 3600,
        'originalValue' => 100.0,
        'pixKey' => 'k',
        'description' => 'd',
    ]);
});
