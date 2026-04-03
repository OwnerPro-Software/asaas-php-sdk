<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\SplitRefund;

mutates(SplitRefund::class);

it('creates from array', function (): void {
    $refund = SplitRefund::fromArray(['id' => 'split_123', 'value' => 25.00]);

    expect($refund->id)->toBe('split_123');
    expect($refund->value)->toBe(25.00);
});

it('converts to array', function (): void {
    $refund = new SplitRefund(id: 'split_123', value: 25.00);

    expect($refund->toArray())->toBe(['id' => 'split_123', 'value' => 25.00]);
});

it('throws when required field is missing', function (string $missingField): void {
    $data = ['id' => 'split_123', 'value' => 25.00];

    unset($data[$missingField]);

    SplitRefund::fromArray($data);
})->throws(TypeError::class)->with([
    'id',
    'value',
]);
