<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\Missing;

mutates(Discount::class);

it('builds from array with full shape', function (): void {
    $discount = Discount::fromArray([
        'value' => 10.0,
        'dueDateLimitDays' => 5,
        'type' => DiscountType::Percentage,
    ]);

    expect($discount->value)->toBe(10.0);
    expect($discount->dueDateLimitDays)->toBe(5);
    expect($discount->type)->toBe(DiscountType::Percentage);
});

it('builds from array with only required value', function (): void {
    $discount = Discount::fromArray(['value' => 7.5]);

    expect($discount->value)->toBe(7.5);
    expect($discount->dueDateLimitDays)->toBeNull();
    expect($discount->type)->toBeNull();
});

it('throws when value is missing', function (): void {
    Discount::fromArray([]);
})->throws(InvalidArgumentException::class, 'Discount: value is required');

it('coerces float to Discount with value only', function (): void {
    $discount = Discount::coerce(12.5);

    expect($discount)->toBeInstanceOf(Discount::class);
    expect($discount->value)->toBe(12.5);
    expect($discount->dueDateLimitDays)->toBeNull();
    expect($discount->type)->toBeNull();
});

it('coerces array via fromArray', function (): void {
    $discount = Discount::coerce(['value' => 3.0, 'type' => 'FIXED']);

    expect($discount)->toBeInstanceOf(Discount::class);
    expect($discount->value)->toBe(3.0);
    expect($discount->type)->toBe('FIXED');
});

it('passes through Discount instance untouched', function (): void {
    $original = new Discount(value: 4.0);

    expect(Discount::coerce($original))->toBe($original);
});

it('preserves null and Missing without wrapping', function (): void {
    expect(Discount::coerce(null))->toBeNull();
    expect(Discount::coerce(Missing::Value))->toBe(Missing::Value);
});

it('serializes object shape on toArray', function (): void {
    expect((new Discount(value: 5.0))->toArray())->toBe(['value' => 5.0]);
    expect((new Discount(value: 5.0, dueDateLimitDays: 3, type: DiscountType::Fixed))->toArray())->toBe([
        'value' => 5.0,
        'dueDateLimitDays' => 3,
        'type' => 'FIXED',
    ]);
});
