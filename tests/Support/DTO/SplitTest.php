<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\Split;

mutates(Split::class);

it('creates from array with all fields', function (): void {
    $split = Split::fromArray([
        'walletId' => 'wal_123',
        'fixedValue' => 10.00,
        'percentualValue' => 5.0,
        'totalFixedValue' => 50.00,
        'externalReference' => 'ref_1',
        'description' => 'Partner split',
    ]);

    expect($split->walletId)->toBe('wal_123');
    expect($split->fixedValue)->toBe(10.00);
    expect($split->percentualValue)->toBe(5.0);
    expect($split->totalFixedValue)->toBe(50.00);
    expect($split->externalReference)->toBe('ref_1');
    expect($split->description)->toBe('Partner split');
});

it('converts to array filtering nulls', function (): void {
    $split = new Split(walletId: 'wal_123', fixedValue: 10.00);

    expect($split->toArray())->toBe(['walletId' => 'wal_123', 'fixedValue' => 10.00]);
});

it('throws when walletId is missing', function (): void {
    Split::fromArray([]);
})->throws(InvalidArgumentException::class);
