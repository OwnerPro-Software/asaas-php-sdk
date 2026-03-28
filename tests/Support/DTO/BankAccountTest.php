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
    expect($account->bank)->toBe(['code' => '001']);
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
        accountName: 'Main Account',
        ownerBirthDate: '1990-01-01',
    );

    $debug = $account->__debugInfo();

    expect($debug['ownerName'])->toBe('John Doe');
    expect($debug['cpfCnpj'])->toBe('********901');
    expect($debug['agency'])->toBe('1234');
    expect($debug['account'])->toBe('***89');
    expect($debug['accountDigit'])->toBe('*');
    expect($debug['bank'])->toBeNull();
    expect($debug['accountName'])->toBe('Main Account');
    expect($debug['ownerBirthDate'])->toBe('***');
});

it('masks sensitive data in debug info with null optionals', function (): void {
    $account = new BankAccount(
        ownerName: 'John Doe',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
    );

    $debug = $account->__debugInfo();

    expect($debug['ownerBirthDate'])->toBeNull();
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
