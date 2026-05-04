<?php

declare(strict_types=1);

use OwnerPro\Asaas\Pix\PixAddressKeyType;
use OwnerPro\Asaas\Support\BankAccountType;
use OwnerPro\Asaas\Support\DTO\Bank;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Transfer\Request\TransferRequest;
use OwnerPro\Asaas\Transfer\TransferOperationType;

mutates(TransferRequest::class);

it('creates from array with pix key', function (): void {
    $request = TransferRequest::fromArray([
        'value' => 100.50,
        'pixAddressKey' => 'jdoe@example.com',
        'pixAddressKeyType' => PixAddressKeyType::Email,
    ]);

    expect($request->value)->toBe(100.50);
    expect($request->pixAddressKey)->toBe('jdoe@example.com');
    expect($request->pixAddressKeyType)->toBe(PixAddressKeyType::Email);
    expect($request->bankAccount)->toBeNull();
});

it('creates from array with bank account', function (): void {
    $request = TransferRequest::fromArray([
        'value' => 250.00,
        'bankAccount' => [
            'ownerName' => 'John',
            'cpfCnpj' => '12345678900',
            'agency' => '0001',
            'account' => '12345',
            'accountDigit' => '6',
            'bank' => ['code' => '341'],
            'bankAccountType' => BankAccountType::CheckingAccount,
        ],
        'operationType' => TransferOperationType::Ted,
        'description' => 'salary',
    ]);

    expect($request->bankAccount)->toBeInstanceOf(BankAccount::class);
    expect($request->bankAccount->ownerName)->toBe('John');
    expect($request->operationType)->toBe(TransferOperationType::Ted);
    expect($request->description)->toBe('salary');
});

it('throws when value is missing', function (): void {
    TransferRequest::fromArray([]);
})->throws(InvalidArgumentException::class, 'TransferRequest: value is required');

it('serializes nested BankAccount in toArray', function (): void {
    $request = new TransferRequest(
        value: 100.00,
        bankAccount: new BankAccount(
            ownerName: 'John',
            cpfCnpj: '12345678900',
            agency: '0001',
            account: '12345',
            accountDigit: '6',
            bank: new Bank(code: '341'),
        ),
    );

    $array = $request->toArray();

    expect($array['value'])->toBe(100.00);
    expect($array['bankAccount']['ownerName'])->toBe('John');
    expect($array['bankAccount']['account'])->toBe('12345');
});

it('masks pix key and nested bank account in debug info', function (): void {
    $request = new TransferRequest(
        value: 100.00,
        pixAddressKey: 'jdoe@example.com',
        pixAddressKeyType: PixAddressKeyType::Email,
        walletId: 'wal_001',
    );

    $debug = $request->__debugInfo();

    expect($debug['value'])->toBe(100.00);
    expect($debug['pixAddressKey'])->toBe('************.com');
    expect($debug['pixAddressKeyType'])->toBe(PixAddressKeyType::Email);
    expect($debug['bankAccount'])->toBeNull();
    expect($debug['walletId'])->toBe('wal_001');
});

it('masks nested bank account in debug info', function (): void {
    $request = new TransferRequest(
        value: 100.00,
        bankAccount: new BankAccount(
            ownerName: 'John',
            cpfCnpj: '12345678900',
            agency: '0001',
            account: '12345',
            accountDigit: '6',
        ),
    );

    $debug = $request->__debugInfo();

    expect($debug['pixAddressKey'])->toBeNull();
    expect($debug['bankAccount']['cpfCnpj'])->toBe('********900');
    expect($debug['bankAccount']['account'])->toBe('***45');
    expect($debug['bankAccount']['accountDigit'])->toBe('*');
});

it('cannot be serialized', function (): void {
    $request = new TransferRequest(
        value: 100.00,
        pixAddressKey: 'jdoe@example.com',
        pixAddressKeyType: PixAddressKeyType::Email,
    );

    serialize($request);
})->throws(LogicException::class);
