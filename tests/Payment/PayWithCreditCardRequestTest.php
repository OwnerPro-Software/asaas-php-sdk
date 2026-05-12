<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

mutates(PayWithCreditCardRequest::class);

it('creates from array with card and holder info', function (): void {
    $request = PayWithCreditCardRequest::fromArray([
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);

    expect($request->creditCard)->toBeInstanceOf(CreditCard::class);
    expect($request->creditCardHolderInfo)->toBeInstanceOf(CreditCardHolderInfo::class);
    expect($request->remoteIp)->toBe('203.0.113.42');
    expect($request->creditCardToken)->toBeNull();
});

it('accepts creditCardToken alone without card and holder info', function (): void {
    $request = PayWithCreditCardRequest::fromArray([
        'creditCardToken' => 'tok_abc',
    ]);

    expect($request->creditCardToken)->toBe('tok_abc');
    expect($request->creditCard)->toBeNull();
    expect($request->creditCardHolderInfo)->toBeNull();
    expect($request->remoteIp)->toBeNull();
});

it('serializes creditCardToken in toArray when set', function (): void {
    $request = new PayWithCreditCardRequest(creditCardToken: 'tok_abc');

    expect($request->toArray())->toBe(['creditCardToken' => 'tok_abc']);
});

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

it('produces an exact debug info shape with masked nested DTOs', function (): void {
    $request = new PayWithCreditCardRequest(
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '12345678900', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    expect($request->__debugInfo())->toBe([
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
        'creditCardToken' => null,
    ]);
});

it('masks creditCardToken in debug info when set', function (): void {
    $request = new PayWithCreditCardRequest(creditCardToken: 'tok_super_secret');

    expect($request->__debugInfo()['creditCardToken'])->toBe('***');
});

it('rejects empty payload with neither token nor card+holder', function (): void {
    new PayWithCreditCardRequest;
})->throws(InvalidArgumentException::class, 'PayWithCreditCardRequest: provide either creditCardToken');

it('rejects card without holder info', function (): void {
    new PayWithCreditCardRequest(
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
    );
})->throws(InvalidArgumentException::class, 'PayWithCreditCardRequest: provide either creditCardToken');

it('rejects holder info without card', function (): void {
    new PayWithCreditCardRequest(
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
    );
})->throws(InvalidArgumentException::class, 'PayWithCreditCardRequest: provide either creditCardToken');

it('rejects fromArray with neither token nor card+holder', function (): void {
    PayWithCreditCardRequest::fromArray(['remoteIp' => '203.0.113.42']);
})->throws(InvalidArgumentException::class, 'PayWithCreditCardRequest: provide either creditCardToken');

it('cannot be serialized', function (): void {
    $request = new PayWithCreditCardRequest(
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    );

    serialize($request);
})->throws(LogicException::class);
