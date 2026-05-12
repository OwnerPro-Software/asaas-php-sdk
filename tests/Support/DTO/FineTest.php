<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\Missing;

mutates(Fine::class);

it('builds from array with full shape', function (): void {
    $fine = Fine::fromArray(['value' => 2.0, 'type' => FineType::Fixed]);

    expect($fine->value)->toBe(2.0);
    expect($fine->type)->toBe(FineType::Fixed);
});

it('builds from array with only required value', function (): void {
    $fine = Fine::fromArray(['value' => 1.5]);

    expect($fine->value)->toBe(1.5);
    expect($fine->type)->toBeNull();
});

it('throws when value is missing', function (): void {
    Fine::fromArray([]);
})->throws(InvalidArgumentException::class, 'Fine: value is required');

it('coerces float to Fine with value only', function (): void {
    $fine = Fine::coerce(2.5);

    expect($fine)->toBeInstanceOf(Fine::class);
    expect($fine->value)->toBe(2.5);
    expect($fine->type)->toBeNull();
});

it('coerces array via fromArray', function (): void {
    $fine = Fine::coerce(['value' => 3.0, 'type' => 'PERCENTAGE']);

    expect($fine)->toBeInstanceOf(Fine::class);
    expect($fine->value)->toBe(3.0);
    expect($fine->type)->toBe('PERCENTAGE');
});

it('passes through Fine instance untouched', function (): void {
    $original = new Fine(value: 4.0);

    expect(Fine::coerce($original))->toBe($original);
});

it('preserves null and Missing without wrapping', function (): void {
    expect(Fine::coerce(null))->toBeNull();
    expect(Fine::coerce(Missing::Value))->toBe(Missing::Value);
});

it('serializes object shape on toArray', function (): void {
    expect((new Fine(value: 2.0))->toArray())->toBe(['value' => 2.0]);
    expect((new Fine(value: 2.0, type: FineType::Percentage))->toArray())->toBe([
        'value' => 2.0,
        'type' => 'PERCENTAGE',
    ]);
});
