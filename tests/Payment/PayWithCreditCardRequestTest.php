<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

mutates(PayWithCreditCardRequest::class);

it('creates from array', function (): void {
    $request = PayWithCreditCardRequest::fromArray([
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);

    expect($request->creditCard)->toBeInstanceOf(CreditCard::class);
    expect($request->creditCardHolderInfo)->toBeInstanceOf(CreditCardHolderInfo::class);
    expect($request->remoteIp)->toBe('203.0.113.42');
});

it('throws when creditCard is missing', function (): void {
    PayWithCreditCardRequest::fromArray([
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);
})->throws(InvalidArgumentException::class);

it('throws when creditCardHolderInfo is missing', function (): void {
    PayWithCreditCardRequest::fromArray([
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'remoteIp' => '203.0.113.42',
    ]);
})->throws(InvalidArgumentException::class);

it('throws when remoteIp is missing', function (): void {
    PayWithCreditCardRequest::fromArray([
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
    ]);
})->throws(InvalidArgumentException::class, 'PayWithCreditCardRequest: remoteIp is required');

it('serializes nested CreditCard DTO in toArray', function (): void {
    $request = new PayWithCreditCardRequest(
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    $array = $request->toArray();

    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
    expect($array['creditCardHolderInfo'])->toBe(['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999']);
    expect($array['remoteIp'])->toBe('203.0.113.42');
});

it('passes arrays through as-is in toArray', function (): void {
    $request = new PayWithCreditCardRequest(
        creditCard: ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        creditCardHolderInfo: ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        remoteIp: '203.0.113.42',
    );

    $array = $request->toArray();

    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
});
