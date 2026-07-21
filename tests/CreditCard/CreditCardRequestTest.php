<?php

declare(strict_types=1);

use OwnerPro\Asaas\CreditCard\Request\CreditCardRequest;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

mutates(CreditCardRequest::class);

it('creates from array', function (): void {
    $request = CreditCardRequest::fromArray([
        'customer' => 'cus_001',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '12345678900', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);

    expect($request->customer)->toBe('cus_001');
    expect($request->creditCard)->toBeInstanceOf(CreditCard::class);
    expect($request->creditCardHolderInfo)->toBeInstanceOf(CreditCardHolderInfo::class);
    expect($request->remoteIp)->toBe('203.0.113.42');
});

it('throws when customer is missing', function (): void {
    CreditCardRequest::fromArray([
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);
})->throws(InvalidArgumentException::class, 'CreditCardRequest: customer is required');

it('throws when creditCard is missing', function (): void {
    CreditCardRequest::fromArray([
        'customer' => 'cus_001',
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);
})->throws(InvalidArgumentException::class, 'CreditCardRequest: creditCard is required');

it('throws when creditCardHolderInfo is missing', function (): void {
    CreditCardRequest::fromArray([
        'customer' => 'cus_001',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'remoteIp' => '203.0.113.42',
    ]);
})->throws(InvalidArgumentException::class, 'CreditCardRequest: creditCardHolderInfo is required');

it('throws when remoteIp is missing', function (): void {
    CreditCardRequest::fromArray([
        'customer' => 'cus_001',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
    ]);
})->throws(InvalidArgumentException::class, 'CreditCardRequest: remoteIp is required');

it('serializes nested DTOs in toArray', function (): void {
    $request = new CreditCardRequest(
        customer: 'cus_001',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    $array = $request->toArray();

    expect($array['customer'])->toBe('cus_001');
    expect($array['creditCard']['number'])->toBe('4111111111111111');
    expect($array['creditCardHolderInfo']['email'])->toBe('j@t.com');
    expect($array['remoteIp'])->toBe('203.0.113.42');
});

it('produces an exact debug info shape with masked nested DTOs', function (): void {
    $request = new CreditCardRequest(
        customer: 'cus_001',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '12345678900', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    expect($request->__debugInfo())->toBe([
        'customer' => 'cus_001',
        'creditCard' => [
            'holderName' => 'John',
            'number' => '********1111',
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
    ]);
});

it('cannot be serialized', function (): void {
    $request = new CreditCardRequest(
        customer: 'cus_001',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    serialize($request);
})->throws(LogicException::class);
