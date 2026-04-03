<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\Request\SimulatePaymentRequest;

mutates(SimulatePaymentRequest::class);

it('creates from array with all fields', function (): void {
    $request = SimulatePaymentRequest::fromArray([
        'value' => 1000.00,
        'billingTypes' => ['PIX', 'CREDIT_CARD'],
        'installmentCount' => 3,
    ]);

    expect($request->value)->toBe(1000.00);
    expect($request->billingTypes)->toBe(['PIX', 'CREDIT_CARD']);
    expect($request->installmentCount)->toBe(3);
});

it('converts to array filtering nulls', function (): void {
    $request = new SimulatePaymentRequest(value: 1000.00, billingTypes: ['PIX']);

    expect($request->toArray())->toBe(['value' => 1000.00, 'billingTypes' => ['PIX']]);
});

it('throws when value is missing', function (): void {
    SimulatePaymentRequest::fromArray(['billingTypes' => ['PIX']]);
})->throws(TypeError::class);

it('throws when billingTypes is missing', function (): void {
    SimulatePaymentRequest::fromArray(['value' => 1000.00]);
})->throws(TypeError::class);
