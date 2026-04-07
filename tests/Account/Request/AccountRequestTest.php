<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\Request\AccountRequest;

mutates(AccountRequest::class);

it('creates from array with all fields', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'birthDate' => '1990-01-01',
        'companyType' => 'MEI',
        'phone' => '1133334444',
        'complement' => 'Apt 4',
        'tradingName' => 'JD Corp',
        'site' => 'https://example.com',
    ]);

    expect($request->name)->toBe('John Doe');
    expect($request->email)->toBe('john@example.com');
    expect($request->cpfCnpj)->toBe('12345678901');
    expect($request->mobilePhone)->toBe('11999999999');
    expect($request->incomeValue)->toBe(5000.00);
    expect($request->address)->toBe('Rua Exemplo');
    expect($request->addressNumber)->toBe('123');
    expect($request->province)->toBe('Centro');
    expect($request->postalCode)->toBe('01001000');
    expect($request->birthDate)->toBe('1990-01-01');
    expect($request->companyType)->toBe('MEI');
    expect($request->phone)->toBe('1133334444');
    expect($request->complement)->toBe('Apt 4');
    expect($request->tradingName)->toBe('JD Corp');
    expect($request->site)->toBe('https://example.com');
});

it('creates from array with only required fields', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
    ]);

    expect($request->birthDate)->toBeNull();
    expect($request->companyType)->toBeNull();
    expect($request->phone)->toBeNull();
    expect($request->complement)->toBeNull();
    expect($request->tradingName)->toBeNull();
    expect($request->site)->toBeNull();
});

it('masks sensitive data in debug info', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
        birthDate: '1990-01-01',
        companyType: 'MEI',
        phone: '1133334444',
        complement: 'Apt 4',
        tradingName: 'JD Corp',
        site: 'https://example.com',
    );

    $debug = $request->__debugInfo();

    expect($debug['name'])->toBe('John Doe');
    expect($debug['email'])->toBe('***');
    expect($debug['cpfCnpj'])->toBe('********901');
    expect($debug['mobilePhone'])->toBe('***');
    expect($debug['incomeValue'])->toBe(5000.00);
    expect($debug['address'])->toBe('Rua Exemplo');
    expect($debug['addressNumber'])->toBe('123');
    expect($debug['province'])->toBe('Centro');
    expect($debug['postalCode'])->toBe('01001000');
    expect($debug['birthDate'])->toBe('***');
    expect($debug['companyType'])->toBe('MEI');
    expect($debug['phone'])->toBe('***');
    expect($debug['complement'])->toBe('Apt 4');
    expect($debug['tradingName'])->toBe('JD Corp');
    expect($debug['site'])->toBe('https://example.com');
});

it('shows null optionals as null in debug info', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
    );

    $debug = $request->__debugInfo();

    expect($debug['birthDate'])->toBeNull();
    expect($debug['phone'])->toBeNull();
});

it('masks short cpfCnpj without negative repeat', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '01',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
    );

    expect($request->__debugInfo()['cpfCnpj'])->toBe('01');
});

it('masks sensitive data in json serialization', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
        birthDate: '1990-01-01',
        phone: '1133334444',
    );

    $json = json_decode(json_encode($request), true);

    expect($json['email'])->toBe('***');
    expect($json['cpfCnpj'])->toBe('********901');
    expect($json['mobilePhone'])->toBe('***');
    expect($json['birthDate'])->toBe('***');
    expect($json['phone'])->toBe('***');
});

it('throws when required field is missing', function (string $missingField): void {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
    ];

    unset($data[$missingField]);

    AccountRequest::fromArray($data);
})->throws(InvalidArgumentException::class)->with([
    'name',
    'email',
    'cpfCnpj',
    'mobilePhone',
    'incomeValue',
    'address',
    'addressNumber',
    'province',
    'postalCode',
]);
