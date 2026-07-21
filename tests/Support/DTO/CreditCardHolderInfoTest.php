<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

mutates(CreditCardHolderInfo::class);

it('creates from array with all fields', function (): void {
    $info = CreditCardHolderInfo::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'postalCode' => '01001000',
        'addressNumber' => '123',
        'phone' => '1199999999',
        'addressComplement' => 'Apt 4',
        'mobilePhone' => '11999999999',
    ]);

    expect($info->name)->toBe('John Doe');
    expect($info->addressComplement)->toBe('Apt 4');
    expect($info->mobilePhone)->toBe('11999999999');
});

it('converts to array filtering nulls', function (): void {
    $info = new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '123',
        phone: '1199999999',
    );

    expect($info->toArray())->toBe([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'postalCode' => '01001000',
        'addressNumber' => '123',
        'phone' => '1199999999',
    ]);
});

it('masks sensitive data in debug info', function (): void {
    $info = new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '123',
        phone: '1199999999',
        mobilePhone: '11999999999',
    );

    $debug = $info->__debugInfo();

    expect($debug['name'])->toBe('John Doe');
    expect($debug['email'])->toBe('***');
    expect($debug['cpfCnpj'])->toBe('********901');
    expect($debug['postalCode'])->toBe('01001000');
    expect($debug['addressNumber'])->toBe('123');
    expect($debug['phone'])->toBe('***');
    expect($debug['addressComplement'])->toBeNull();
    expect($debug['mobilePhone'])->toBe('***');
});

it('masks sensitive data in debug info with null mobilePhone', function (): void {
    $info = new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '123',
        phone: '1199999999',
    );

    expect($info->__debugInfo()['mobilePhone'])->toBeNull();
});

it('masks short cpfCnpj without negative repeat', function (): void {
    $info = new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '01',
        postalCode: '01001000',
        addressNumber: '123',
        phone: '1199999999',
    );

    expect($info->__debugInfo()['cpfCnpj'])->toBe('********');
});

it('masks sensitive data in json serialization', function (): void {
    $info = new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '123',
        phone: '1199999999',
        mobilePhone: '11999999999',
    );

    $json = json_decode(json_encode($info), true);

    expect($json['name'])->toBe('John Doe');
    expect($json['email'])->toBe('***');
    expect($json['cpfCnpj'])->toBe('********901');
    expect($json['phone'])->toBe('***');
    expect($json['mobilePhone'])->toBe('***');
});

it('throws when required field is missing', function (string $missingField): void {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'postalCode' => '01001000',
        'addressNumber' => '123',
        'phone' => '1199999999',
    ];

    unset($data[$missingField]);

    CreditCardHolderInfo::fromArray($data);
})->throws(InvalidArgumentException::class)->with([
    'name',
    'email',
    'cpfCnpj',
    'postalCode',
    'addressNumber',
    'phone',
]);

it('cannot be serialized', function (): void {
    $info = new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678900',
        postalCode: '01001000',
        addressNumber: '1',
        phone: '11999990000',
    );

    serialize($info);
})->throws(LogicException::class);
