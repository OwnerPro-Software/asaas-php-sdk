<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Support\DTO\SplitRefund;

mutates(RefundPaymentRequest::class);

it('creates from array with all fields', function (): void {
    $request = RefundPaymentRequest::fromArray([
        'value' => 50.00,
        'description' => 'Partial refund',
        'splitRefunds' => [['id' => 'split_1', 'value' => 25.00]],
    ]);

    expect($request->value)->toBe(50.00);
    expect($request->description)->toBe('Partial refund');
    expect($request->splitRefunds)->toHaveCount(1);
});

it('hydrates splitRefunds from arrays into SplitRefund objects', function (): void {
    $request = RefundPaymentRequest::fromArray([
        'value' => 50.00,
        'splitRefunds' => [
            ['id' => 'split_1', 'value' => 25.00],
            ['id' => 'split_2', 'value' => 25.00],
        ],
    ]);

    expect($request->splitRefunds)->toHaveCount(2);
    expect($request->splitRefunds[0])->toBeInstanceOf(SplitRefund::class);
    expect($request->splitRefunds[0]->id)->toBe('split_1');
    expect($request->splitRefunds[1]->id)->toBe('split_2');
});

it('creates from empty array', function (): void {
    $request = RefundPaymentRequest::fromArray([]);

    expect($request->value)->toBeNull();
    expect($request->description)->toBeNull();
    expect($request->splitRefunds)->toBeNull();
});

it('converts to array filtering nulls', function (): void {
    $request = new RefundPaymentRequest(value: 50.00);

    expect($request->toArray())->toBe(['value' => 50.00]);
});

it('serializes nested SplitRefund DTOs in toArray', function (): void {
    $request = new RefundPaymentRequest(
        value: 50.00,
        splitRefunds: [new SplitRefund(id: 'split_1', value: 25.00)],
    );

    expect($request->toArray())->toBe([
        'value' => 50.00,
        'splitRefunds' => [['id' => 'split_1', 'value' => 25.00]],
    ]);
});

it('passes array splitRefunds through as-is in toArray', function (): void {
    $request = new RefundPaymentRequest(
        value: 50.00,
        splitRefunds: [['id' => 'split_1', 'value' => 25.00]],
    );

    expect($request->toArray())->toBe([
        'value' => 50.00,
        'splitRefunds' => [['id' => 'split_1', 'value' => 25.00]],
    ]);
});
