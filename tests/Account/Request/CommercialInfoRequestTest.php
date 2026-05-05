<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\CompanyType;
use OwnerPro\Asaas\Account\Request\CommercialInfoRequest;

mutates(CommercialInfoRequest::class);

it('builds from array with only provided fields', function (): void {
    $req = CommercialInfoRequest::fromArray([
        'birthDate' => '1990-01-01',
        'incomeValue' => 12000.0,
    ]);

    expect($req->toArray())->toBe([
        'birthDate' => '1990-01-01',
        'incomeValue' => 12000.0,
    ]);
});

it('omits fields left as Missing on partial updates', function (): void {
    $req = new CommercialInfoRequest(incomeValue: 7500.0);

    expect($req->toArray())->toBe(['incomeValue' => 7500.0]);
});

it('serialises CompanyType enum to its value', function (): void {
    $req = new CommercialInfoRequest(companyType: CompanyType::Limited);

    expect($req->toArray())->toBe(['companyType' => 'LIMITED']);
});

it('keeps companyType string passthrough', function (): void {
    $req = new CommercialInfoRequest(companyType: 'MEI');

    expect($req->toArray())->toBe(['companyType' => 'MEI']);
});

it('builds from array with all fields populated', function (): void {
    $req = CommercialInfoRequest::fromArray([
        'name' => 'Acme Ltd',
        'email' => 'contact@acme.test',
        'cpfCnpj' => '12345678000199',
        'mobilePhone' => '11999999999',
        'phone' => '1133334444',
        'birthDate' => '1980-05-12',
        'companyType' => 'LIMITED',
        'incomeValue' => 25000.0,
        'address' => 'Av Paulista',
        'addressNumber' => '1000',
        'complement' => 'Sala 101',
        'province' => 'Bela Vista',
        'postalCode' => '01310100',
        'tradingName' => 'Acme',
        'site' => 'https://acme.test',
    ]);

    expect($req->toArray())->toBe([
        'name' => 'Acme Ltd',
        'email' => 'contact@acme.test',
        'cpfCnpj' => '12345678000199',
        'mobilePhone' => '11999999999',
        'phone' => '1133334444',
        'birthDate' => '1980-05-12',
        'companyType' => 'LIMITED',
        'incomeValue' => 25000.0,
        'address' => 'Av Paulista',
        'addressNumber' => '1000',
        'complement' => 'Sala 101',
        'province' => 'Bela Vista',
        'postalCode' => '01310100',
        'tradingName' => 'Acme',
        'site' => 'https://acme.test',
    ]);
});

it('masks sensitive fields in __debugInfo', function (): void {
    $req = new CommercialInfoRequest(
        birthDate: '1990-01-01',
        cpfCnpj: '12345678900',
    );

    expect($req->__debugInfo()['birthDate'])->toBe('***');
    expect($req->__debugInfo()['cpfCnpj'])->toBe('********900');
});

it('masks email/mobilePhone/phone when present in __debugInfo', function (): void {
    $req = new CommercialInfoRequest(
        email: 'john@example.com',
        mobilePhone: '11999999999',
        phone: '1133334444',
    );

    $debug = $req->__debugInfo();

    expect($debug['email'])->toBe('***');
    expect($debug['mobilePhone'])->toBe('***');
    expect($debug['phone'])->toBe('***');
});

it('omits Missing fields from __debugInfo entirely', function (): void {
    $req = new CommercialInfoRequest;

    expect($req->__debugInfo())->toBe([]);
});

it('exposes only the provided fields in __debugInfo', function (): void {
    $req = new CommercialInfoRequest(
        name: 'Acme',
        incomeValue: 1000.0,
    );

    $debug = $req->__debugInfo();

    expect($debug)->toBe([
        'name' => 'Acme',
        'incomeValue' => 1000.0,
    ]);
});

it('masks sensitive data in json serialization', function (): void {
    $req = new CommercialInfoRequest(
        email: 'john@example.com',
        cpfCnpj: '12345678900',
        mobilePhone: '11999999999',
        phone: '1133334444',
        birthDate: '1990-01-01',
    );

    $json = json_decode(json_encode($req), true);

    expect($json['email'])->toBe('***');
    expect($json['cpfCnpj'])->toBe('********900');
    expect($json['mobilePhone'])->toBe('***');
    expect($json['phone'])->toBe('***');
    expect($json['birthDate'])->toBe('***');
});

it('refuses to be serialised', function (): void {
    serialize(new CommercialInfoRequest(incomeValue: 1.0));
})->throws(LogicException::class);
