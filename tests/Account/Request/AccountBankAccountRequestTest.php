<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\Request\AccountBankAccountRequest;
use OwnerPro\Asaas\Support\BankAccountType;

mutates(AccountBankAccountRequest::class);

it('builds with required fields and serialises bank as nested object', function (): void {
    $req = new AccountBankAccountRequest(
        bankCode: '341',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
        accountType: BankAccountType::CheckingAccount,
    );

    expect($req->toArray())->toBe([
        'bank' => ['code' => '341'],
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'bankAccountType' => 'CONTA_CORRENTE',
    ]);
});

it('keeps optional pix fields when provided', function (): void {
    $req = new AccountBankAccountRequest(
        bankCode: '341',
        agency: '1',
        account: '1',
        accountDigit: '1',
        accountType: 'CONTA_POUPANCA',
        pixAddressKey: 'pix@test.com',
        pixAddressKeyType: 'EMAIL',
    );

    expect($req->toArray())->toBe([
        'bank' => ['code' => '341'],
        'agency' => '1',
        'account' => '1',
        'accountDigit' => '1',
        'bankAccountType' => 'CONTA_POUPANCA',
        'pixAddressKey' => 'pix@test.com',
        'pixAddressKeyType' => 'EMAIL',
    ]);
});

it('omits pix fields when null', function (): void {
    $req = new AccountBankAccountRequest(
        bankCode: '341',
        agency: '1',
        account: '1',
        accountDigit: '1',
        accountType: 'CONTA_CORRENTE',
    );

    expect($req->toArray())->not->toHaveKey('pixAddressKey');
    expect($req->toArray())->not->toHaveKey('pixAddressKeyType');
});

it('builds from array', function (): void {
    $req = AccountBankAccountRequest::fromArray([
        'bankCode' => '341',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'accountType' => 'CONTA_CORRENTE',
    ]);

    expect($req->toArray())->toBe([
        'bank' => ['code' => '341'],
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'bankAccountType' => 'CONTA_CORRENTE',
    ]);
});

it('builds from array with pix fields', function (): void {
    $req = AccountBankAccountRequest::fromArray([
        'bankCode' => '341',
        'agency' => '1',
        'account' => '1',
        'accountDigit' => '1',
        'accountType' => 'CONTA_CORRENTE',
        'pixAddressKey' => 'pix@test.com',
        'pixAddressKeyType' => 'EMAIL',
    ]);

    expect($req->pixAddressKey)->toBe('pix@test.com');
    expect($req->pixAddressKeyType)->toBe('EMAIL');
});

it('throws on missing required fields', function (string $missingField): void {
    $data = [
        'bankCode' => '341',
        'agency' => '1',
        'account' => '1',
        'accountDigit' => '1',
        'accountType' => 'CONTA_CORRENTE',
    ];

    unset($data[$missingField]);

    AccountBankAccountRequest::fromArray($data);
})->throws(InvalidArgumentException::class)->with([
    'bankCode',
    'agency',
    'account',
    'accountDigit',
    'accountType',
]);

it('masks account in __debugInfo', function (): void {
    $req = new AccountBankAccountRequest(
        bankCode: '341',
        agency: '1234',
        account: '12345',
        accountDigit: '0',
        accountType: 'CONTA_CORRENTE',
    );

    expect($req->__debugInfo()['account'])->toBe('***45');
    expect($req->__debugInfo()['accountDigit'])->toBe('*');
});

it('exposes non-sensitive fields verbatim in __debugInfo', function (): void {
    $req = new AccountBankAccountRequest(
        bankCode: '341',
        agency: '1234',
        account: '12345',
        accountDigit: '0',
        accountType: BankAccountType::SavingsAccount,
        pixAddressKey: 'k',
        pixAddressKeyType: 'EMAIL',
    );

    $debug = $req->__debugInfo();

    expect($debug['bankCode'])->toBe('341');
    expect($debug['agency'])->toBe('1234');
    expect($debug['accountType'])->toBe(BankAccountType::SavingsAccount);
    expect($debug['pixAddressKey'])->toBe('k');
    expect($debug['pixAddressKeyType'])->toBe('EMAIL');
});

it('refuses to be serialised', function (): void {
    serialize(new AccountBankAccountRequest(
        bankCode: '341',
        agency: '1',
        account: '1',
        accountDigit: '1',
        accountType: 'CONTA_CORRENTE',
    ));
})->throws(LogicException::class);
