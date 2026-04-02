<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\Bank;
use OwnerPro\Asaas\Support\DTO\BankAccount;

mutates(BankAccount::class);

it('creates from array with all fields', function (): void {
    $account = BankAccount::fromArray([
        'ownerName' => 'John Doe',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'bank' => ['code' => '001'],
        'accountName' => 'Main Account',
        'ownerBirthDate' => '1990-01-01',
        'bankAccountType' => 'CONTA_CORRENTE',
        'ispb' => '00000000',
    ]);

    expect($account->ownerName)->toBe('John Doe');
    expect($account->bank)->toBeInstanceOf(Bank::class);
    expect($account->bank->code)->toBe('001');
    expect($account->bankAccountType)->toBe('CONTA_CORRENTE');
});

it('converts to array filtering nulls', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
    );

    expect($account->toArray())->toBe([
        'ownerName' => 'John Doe',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
    ]);
});

it('serializes nested Bank DTO in toArray', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
        bank: new Bank(code: '001'),
    );

    expect($account->toArray())->toBe([
        'ownerName' => 'John Doe',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'bank' => ['code' => '001'],
    ]);
});

it('passes array bank through as-is in toArray', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
        bank: ['code' => '001'],
    );

    expect($account->toArray())->toMatchArray(['bank' => ['code' => '001']]);
});

it('masks sensitive data in debug info', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
        bank: ['code' => '001'],
        accountName: 'Main Account',
        ownerBirthDate: '1990-01-01',
        bankAccountType: 'CONTA_CORRENTE',
        ispb: '00000000',
    );

    expect($account->__debugInfo())->toBe([
        'ownerName' => 'John Doe',
        'cpfCnpj' => '********901',
        'agency' => '1234',
        'account' => '***89',
        'accountDigit' => '*',
        'bank' => ['code' => '001'],
        'accountName' => 'Main Account',
        'ownerBirthDate' => '***',
        'bankAccountType' => 'CONTA_CORRENTE',
        'ispb' => '00000000',
    ]);
});

it('masks sensitive data in debug info with null optionals', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
    );

    expect($account->__debugInfo())->toBe([
        'ownerName' => 'John Doe',
        'cpfCnpj' => '********901',
        'agency' => '1234',
        'account' => '***89',
        'accountDigit' => '*',
        'bank' => null,
        'accountName' => null,
        'ownerBirthDate' => null,
        'bankAccountType' => null,
        'ispb' => null,
    ]);
});

it('masks short cpfCnpj and account without negative repeat', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '01',
        agency: '1234',
        account: '9',
        accountDigit: '0',
    );

    $debug = $account->__debugInfo();

    expect($debug['cpfCnpj'])->toBe('01');
    expect($debug['account'])->toBe('9');
});

it('throws when required field is missing', function (string $missingField): void {
    $data = [
        'ownerName' => 'John Doe',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
    ];

    unset($data[$missingField]);

    BankAccount::fromArray($data);
})->throws(InvalidArgumentException::class)->with([
    'ownerName',
    'cpfCnpj',
    'agency',
    'account',
    'accountDigit',
]);
