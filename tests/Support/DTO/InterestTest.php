<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\Interest;
use OwnerPro\Asaas\Support\Missing;

mutates(Interest::class);

it('builds from array with value', function (): void {
    $interest = Interest::fromArray(['value' => 1.5]);

    expect($interest->value)->toBe(1.5);
});

it('throws when value is missing', function (): void {
    Interest::fromArray([]);
})->throws(InvalidArgumentException::class, 'Interest: value is required');

it('coerces float to Interest', function (): void {
    $interest = Interest::coerce(2.0);

    expect($interest)->toBeInstanceOf(Interest::class);
    expect($interest->value)->toBe(2.0);
});

it('coerces array via fromArray', function (): void {
    $interest = Interest::coerce(['value' => 3.0]);

    expect($interest)->toBeInstanceOf(Interest::class);
    expect($interest->value)->toBe(3.0);
});

it('passes through Interest instance untouched', function (): void {
    $original = new Interest(value: 4.0);

    expect(Interest::coerce($original))->toBe($original);
});

it('preserves null and Missing without wrapping', function (): void {
    expect(Interest::coerce(null))->toBeNull();
    expect(Interest::coerce(Missing::Value))->toBe(Missing::Value);
});

it('serializes object shape on toArray', function (): void {
    expect((new Interest(value: 1.5))->toArray())->toBe(['value' => 1.5]);
});
