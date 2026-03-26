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
