<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Split;

mutates(CreatePaymentRequest::class);

it('creates from array with required fields only', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
    ]);

    expect($request->customer)->toBe('cus_001');
    expect($request->billingType)->toBe(BillingType::Pix);
    expect($request->value)->toBe(100.00);
    expect($request->dueDate)->toBe('2026-04-01');
    expect($request->creditCard)->toBeNull();
    expect($request->split)->toBeNull();
});

it('creates from array with all nested DTOs', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::CreditCard,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '12345678900', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 10.0]],
        'callback' => ['successUrl' => 'https://example.com/ok', 'autoRedirect' => true],
        'remoteIp' => '203.0.113.42',
    ]);

    expect($request->creditCard)->toBeInstanceOf(CreditCard::class);
    expect($request->creditCardHolderInfo)->toBeInstanceOf(CreditCardHolderInfo::class);
    expect($request->callback)->toBeInstanceOf(Callback::class);
    expect($request->split)->toHaveCount(1);
    expect($request->split[0])->toBeInstanceOf(Split::class);
});

it('throws when customer is missing', function (): void {
    CreatePaymentRequest::fromArray([
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
    ]);
})->throws(InvalidArgumentException::class, 'CreatePaymentRequest: customer is required');

it('throws when billingType is missing', function (): void {
    CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'value' => 100.00,
        'dueDate' => '2026-04-01',
    ]);
})->throws(InvalidArgumentException::class, 'CreatePaymentRequest: billingType is required');

it('throws when value is missing', function (): void {
    CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'dueDate' => '2026-04-01',
    ]);
})->throws(InvalidArgumentException::class, 'CreatePaymentRequest: value is required');

it('throws when dueDate is missing', function (): void {
    CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
    ]);
})->throws(InvalidArgumentException::class, 'CreatePaymentRequest: dueDate is required');

it('serializes nested DTOs in toArray', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::CreditCard,
        value: 100.00,
        dueDate: '2026-04-01',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
    );

    $array = $request->toArray();

    expect($array['creditCard']['number'])->toBe('4111111111111111');
    expect($array['creditCardHolderInfo']['email'])->toBe('j@t.com');
});

it('masks nested credit card and holder info in debug', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::CreditCard,
        value: 100.00,
        dueDate: '2026-04-01',
        description: 'order_001',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '12345678900', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    $debug = $request->__debugInfo();

    expect($debug['customer'])->toBe('cus_001');
    expect($debug['billingType'])->toBe(BillingType::CreditCard);
    expect($debug['value'])->toBe(100.00);
    expect($debug['dueDate'])->toBe('2026-04-01');
    expect($debug['description'])->toBe('order_001');
    expect($debug['creditCard']['number'])->toBe('************1111');
    expect($debug['creditCardHolderInfo']['cpfCnpj'])->toBe('********900');
    expect($debug['remoteIp'])->toBe('203.0.113.42');
});

it('returns nulls for unset fields in debug info', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-04-01',
    );

    $debug = $request->__debugInfo();

    expect($debug['creditCard'])->toBeNull();
    expect($debug['creditCardHolderInfo'])->toBeNull();
    expect($debug['split'])->toBeNull();
    expect($debug['callback'])->toBeNull();
    expect($debug['remoteIp'])->toBeNull();
});

it('cannot be serialized', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-04-01',
    );

    serialize($request);
})->throws(LogicException::class);
