<?php

declare(strict_types=1);

use OwnerPro\Asaas\Transfer\Request\Recurring;
use OwnerPro\Asaas\Transfer\TransferRecurrenceFrequency;

mutates(Recurring::class);

it('builds from array with string frequency', function (): void {
    $recurring = Recurring::fromArray(['frequency' => 'MONTHLY', 'quantity' => 12]);

    expect($recurring->frequency)->toBe('MONTHLY');
    expect($recurring->quantity)->toBe(12);
});

it('builds from array with enum frequency', function (): void {
    $recurring = Recurring::fromArray([
        'frequency' => TransferRecurrenceFrequency::Weekly,
        'quantity' => 4,
    ]);

    expect($recurring->frequency)->toBe(TransferRecurrenceFrequency::Weekly);
    expect($recurring->quantity)->toBe(4);
});

it('throws when frequency is missing', function (): void {
    Recurring::fromArray(['quantity' => 1]);
})->throws(InvalidArgumentException::class, 'Recurring: frequency is required');

it('throws when quantity is missing', function (): void {
    Recurring::fromArray(['frequency' => 'WEEKLY']);
})->throws(InvalidArgumentException::class, 'Recurring: quantity is required');

it('serializes enum frequency to its string value via toArray', function (): void {
    $recurring = new Recurring(frequency: TransferRecurrenceFrequency::Monthly, quantity: 6);

    expect($recurring->toArray())->toBe(['frequency' => 'MONTHLY', 'quantity' => 6]);
});

it('serializes string frequency unchanged via toArray', function (): void {
    $recurring = new Recurring(frequency: 'WEEKLY', quantity: 2);

    expect($recurring->toArray())->toBe(['frequency' => 'WEEKLY', 'quantity' => 2]);
});

// Documented range per PHPDoc: 1..51 for WEEKLY, 1..11 for MONTHLY.
// SDK does not enforce the range at runtime (delegated to Asaas server).
// These pins confirm boundary values are accepted by the DTO and round-trip
// unchanged, so the only validation point is server-side.
it('accepts documented boundary quantities for each frequency', function (TransferRecurrenceFrequency $frequency, int $quantity): void {
    $recurring = new Recurring(frequency: $frequency, quantity: $quantity);

    expect($recurring->quantity)->toBe($quantity);
    expect($recurring->toArray()['quantity'])->toBe($quantity);
})->with([
    'WEEKLY lower bound' => [TransferRecurrenceFrequency::Weekly, 1],
    'WEEKLY upper bound' => [TransferRecurrenceFrequency::Weekly, 51],
    'MONTHLY lower bound' => [TransferRecurrenceFrequency::Monthly, 1],
    'MONTHLY upper bound' => [TransferRecurrenceFrequency::Monthly, 11],
]);
