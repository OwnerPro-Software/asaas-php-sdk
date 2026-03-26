<?php

declare(strict_types=1);

use OwnerPro\Asaas\BillPayment\Request\SimulateBillPaymentRequest;

mutates(SimulateBillPaymentRequest::class);

it('creates from array with all fields', function (): void {
    $request = SimulateBillPaymentRequest::fromArray([
        'identificationField' => '12345.67890',
        'barCode' => '12345678901234567890',
    ]);

    expect($request->identificationField)->toBe('12345.67890');
    expect($request->barCode)->toBe('12345678901234567890');
});

it('creates from empty array', function (): void {
    $request = SimulateBillPaymentRequest::fromArray([]);

    expect($request->identificationField)->toBeNull();
    expect($request->barCode)->toBeNull();
});

it('converts to array filtering nulls', function (): void {
    $request = new SimulateBillPaymentRequest(identificationField: '12345.67890');

    expect($request->toArray())->toBe(['identificationField' => '12345.67890']);
});
