<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\Request\ReceivePaymentInCashRequest;

mutates(ReceivePaymentInCashRequest::class);

it('creates from array with all fields', function (): void {
    $request = ReceivePaymentInCashRequest::fromArray([
        'paymentDate' => '2026-03-26',
        'value' => 100.00,
        'notifyCustomer' => true,
    ]);

    expect($request->paymentDate)->toBe('2026-03-26');
    expect($request->value)->toBe(100.00);
    expect($request->notifyCustomer)->toBeTrue();
});

it('creates from empty array', function (): void {
    $request = ReceivePaymentInCashRequest::fromArray([]);

    expect($request->paymentDate)->toBeNull();
    expect($request->value)->toBeNull();
    expect($request->notifyCustomer)->toBeNull();
});

it('converts to array filtering nulls', function (): void {
    $request = new ReceivePaymentInCashRequest(paymentDate: '2026-03-26');

    expect($request->toArray())->toBe(['paymentDate' => '2026-03-26']);
});
