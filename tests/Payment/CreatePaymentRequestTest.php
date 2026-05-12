<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\DTO\Interest;
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

it('persists every optional scalar field passed via fromArray', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'description' => 'order_001',
        'externalReference' => 'ext_42',
        'postalService' => true,
        'remoteIp' => '203.0.113.42',
        'authorizeOnly' => true,
        'creditCardToken' => 'tok_xyz',
    ]);

    expect($request->description)->toBe('order_001');
    expect($request->externalReference)->toBe('ext_42');
    expect($request->postalService)->toBeTrue();
    expect($request->remoteIp)->toBe('203.0.113.42');
    expect($request->authorizeOnly)->toBeTrue();
    expect($request->creditCardToken)->toBe('tok_xyz');
});

it('serialises creditCardToken in toArray when set', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::CreditCard,
        value: 150.00,
        dueDate: '2026-04-01',
        remoteIp: '203.0.113.42',
        creditCardToken: 'tok_xyz',
    );

    expect($request->toArray()['creditCardToken'])->toBe('tok_xyz');
});

it('masks creditCardToken on __debugInfo when present', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::CreditCard,
        value: 150.00,
        dueDate: '2026-04-01',
        creditCardToken: 'tok_xyz',
    );

    expect($request->__debugInfo()['creditCardToken'])->toBe('***');
});

it('accepts discount/interest/fine as legacy floats and coerces to value objects', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'discount' => 5.0,
        'interest' => 1.5,
        'fine' => 2.5,
    ]);

    expect($request->discount)->toBeInstanceOf(Discount::class);
    expect($request->discount->value)->toBe(5.0);
    expect($request->interest)->toBeInstanceOf(Interest::class);
    expect($request->interest->value)->toBe(1.5);
    expect($request->fine)->toBeInstanceOf(Fine::class);
    expect($request->fine->value)->toBe(2.5);
});

it('accepts discount/interest/fine as nested arrays with full shape', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'discount' => ['value' => 10.0, 'dueDateLimitDays' => 3, 'type' => DiscountType::Percentage],
        'interest' => ['value' => 1.5],
        'fine' => ['value' => 2.0, 'type' => FineType::Fixed],
    ]);

    expect($request->discount->dueDateLimitDays)->toBe(3);
    expect($request->discount->type)->toBe(DiscountType::Percentage);
    expect($request->interest->value)->toBe(1.5);
    expect($request->fine->type)->toBe(FineType::Fixed);
});

it('serializes discount/interest/fine in toArray as object shape', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-04-01',
        discount: new Discount(value: 10.0, dueDateLimitDays: 3, type: DiscountType::Percentage),
        interest: new Interest(value: 1.5),
        fine: new Fine(value: 2.0, type: FineType::Fixed),
    );

    $array = $request->toArray();

    expect($array['discount'])->toBe(['value' => 10.0, 'dueDateLimitDays' => 3, 'type' => 'PERCENTAGE']);
    expect($array['interest'])->toBe(['value' => 1.5]);
    expect($array['fine'])->toBe(['value' => 2.0, 'type' => 'FIXED']);
});

it('persists every installment and pix-automatic field passed via fromArray', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'daysAfterDueDateToRegistrationCancellation' => 7,
        'installmentCount' => 3,
        'installmentValue' => 33.33,
        'totalValue' => 100.00,
        'pixAutomaticAuthorizationId' => 'auth_abc',
    ]);

    expect($request->daysAfterDueDateToRegistrationCancellation)->toBe(7);
    expect($request->installmentCount)->toBe(3);
    expect($request->installmentValue)->toBe(33.33);
    expect($request->totalValue)->toBe(100.00);
    expect($request->pixAutomaticAuthorizationId)->toBe('auth_abc');
});

it('serializes the new installment and pix-automatic fields in toArray when set', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-04-01',
        daysAfterDueDateToRegistrationCancellation: 7,
        installmentCount: 3,
        installmentValue: 33.33,
        totalValue: 100.00,
        pixAutomaticAuthorizationId: 'auth_abc',
    );

    $array = $request->toArray();

    expect($array['daysAfterDueDateToRegistrationCancellation'])->toBe(7);
    expect($array['installmentCount'])->toBe(3);
    expect($array['installmentValue'])->toBe(33.33);
    expect($array['totalValue'])->toBe(100.00);
    expect($array['pixAutomaticAuthorizationId'])->toBe('auth_abc');
});

it('serializes authorizeOnly in toArray when set', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::CreditCard,
        value: 100.00,
        dueDate: '2026-04-01',
        authorizeOnly: true,
    );

    expect($request->toArray()['authorizeOnly'])->toBeTrue();
});

it('coerces split items to Split DTOs when constructed directly with arrays', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-04-01',
        split: [['walletId' => 'wal_1', 'fixedValue' => 10.0], new Split(walletId: 'wal_2', fixedValue: 20.0)],
    );

    expect($request->split)->toHaveCount(2);
    expect($request->split[0])->toBeInstanceOf(Split::class);
    expect($request->split[0]->walletId)->toBe('wal_1');
    expect($request->split[1])->toBeInstanceOf(Split::class);
    expect($request->split[1]->walletId)->toBe('wal_2');
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

it('produces an exact debug info shape with all scalar fields and masked nested DTOs', function (): void {
    $callback = new Callback(successUrl: 'https://example.com/ok', autoRedirect: true);
    $split = new Split(walletId: 'wal_1', fixedValue: 10.0);
    $discount = new Discount(value: 5.0);
    $interest = new Interest(value: 1.5);
    $fine = new Fine(value: 2.5);
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::CreditCard,
        value: 100.00,
        dueDate: '2026-04-01',
        description: 'order_001',
        externalReference: 'ext_42',
        discount: $discount,
        interest: $interest,
        fine: $fine,
        postalService: true,
        split: [$split],
        callback: $callback,
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '12345678900', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
        authorizeOnly: true,
    );

    expect($request->__debugInfo())->toBe([
        'customer' => 'cus_001',
        'billingType' => BillingType::CreditCard,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'description' => 'order_001',
        'externalReference' => 'ext_42',
        'discount' => $discount,
        'interest' => $interest,
        'fine' => $fine,
        'postalService' => true,
        'daysAfterDueDateToRegistrationCancellation' => null,
        'installmentCount' => null,
        'installmentValue' => null,
        'totalValue' => null,
        'pixAutomaticAuthorizationId' => null,
        'split' => [$split],
        'callback' => $callback,
        'creditCard' => [
            'holderName' => 'John',
            'number' => '************1111',
            'expiryMonth' => '12',
            'expiryYear' => '2030',
            'ccv' => '***',
        ],
        'creditCardHolderInfo' => [
            'name' => 'John',
            'email' => '***',
            'cpfCnpj' => '********900',
            'postalCode' => '01001000',
            'addressNumber' => '1',
            'phone' => '***',
            'addressComplement' => null,
            'mobilePhone' => null,
        ],
        'remoteIp' => '203.0.113.42',
        'authorizeOnly' => true,
        'creditCardToken' => null,
    ]);
});

it('produces an exact debug info shape with nulls for unset optional fields', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_001',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-04-01',
    );

    expect($request->__debugInfo())->toBe([
        'customer' => 'cus_001',
        'billingType' => BillingType::Pix,
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'description' => null,
        'externalReference' => null,
        'discount' => null,
        'interest' => null,
        'fine' => null,
        'postalService' => null,
        'daysAfterDueDateToRegistrationCancellation' => null,
        'installmentCount' => null,
        'installmentValue' => null,
        'totalValue' => null,
        'pixAutomaticAuthorizationId' => null,
        'split' => null,
        'callback' => null,
        'creditCard' => null,
        'creditCardHolderInfo' => null,
        'remoteIp' => null,
        'authorizeOnly' => null,
        'creditCardToken' => null,
    ]);
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
