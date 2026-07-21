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

it('persists every optional scalar field passed via fromArray', function (): void {
    $request = TransferRequest::fromArray([
        'value' => 100.00,
        'pixAddressKey' => 'jdoe@example.com',
        'pixAddressKeyType' => PixAddressKeyType::Email,
        'operationType' => TransferOperationType::Ted,
        'description' => 'salary',
        'scheduleDate' => '2026-04-01',
        'externalReference' => 'ext_42',
    ]);

    expect($request->operationType)->toBe(TransferOperationType::Ted);
    expect($request->description)->toBe('salary');
    expect($request->scheduleDate)->toBe('2026-04-01');
    expect($request->externalReference)->toBe('ext_42');
});

it('produces an exact debug info shape with masked pix key and all scalar fields', function (): void {
    $request = new TransferRequest(
        value: 100.00,
        pixAddressKey: 'jdoe@example.com',
        pixAddressKeyType: PixAddressKeyType::Email,
        operationType: TransferOperationType::Ted,
        description: 'salary',
        scheduleDate: '2026-04-01',
        externalReference: 'ext_42',
    );

    expect($request->__debugInfo())->toBe([
        'value' => 100.00,
        'pixAddressKey' => '********.com',
        'pixAddressKeyType' => PixAddressKeyType::Email,
        'bankAccount' => null,
        'operationType' => TransferOperationType::Ted,
        'description' => 'salary',
        'scheduleDate' => '2026-04-01',
        'externalReference' => 'ext_42',
        'recurring' => null,
    ]);
});

it('produces an exact debug info shape with masked nested bank account', function (): void {
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

    expect($request->__debugInfo())->toBe([
        'value' => 100.00,
        'pixAddressKey' => null,
        'pixAddressKeyType' => null,
        'bankAccount' => [
            'ownerName' => 'John',
            'cpfCnpj' => '********900',
            'agency' => '0001',
            'account' => '********45',
            'accountDigit' => '*',
            'bank' => null,
            'accountName' => null,
            'ownerBirthDate' => null,
            'bankAccountType' => null,
            'ispb' => null,
        ],
        'operationType' => null,
        'description' => null,
        'scheduleDate' => null,
        'externalReference' => null,
        'recurring' => null,
    ]);
});

it('cannot be serialized', function (): void {
    $request = new TransferRequest(
        value: 100.00,
        pixAddressKey: 'jdoe@example.com',
        pixAddressKeyType: PixAddressKeyType::Email,
    );

    serialize($request);
})->throws(LogicException::class);
