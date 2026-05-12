<?php

declare(strict_types=1);

use OwnerPro\Asaas\FiscalInfo\Request\FiscalInfoRequest;

mutates(FiscalInfoRequest::class);

it('builds from array with only the provided fields', function (): void {
    $req = FiscalInfoRequest::fromArray([
        'email' => 'a@b.c',
        'simplesNacional' => true,
        'cnae' => '6209100',
    ]);

    expect($req->toArray())->toBe([
        'email' => 'a@b.c',
        'simplesNacional' => true,
        'cnae' => '6209100',
    ]);
});

it('omits Missing fields entirely', function (): void {
    $req = new FiscalInfoRequest(email: 'a@b.c');

    expect($req->toArray())->toBe(['email' => 'a@b.c']);
});

it('serialises every supported field through fromArray', function (): void {
    $req = FiscalInfoRequest::fromArray([
        'email' => 'a@b.c',
        'simplesNacional' => false,
        'municipalInscription' => '21779501',
        'culturalProjectsPromoter' => true,
        'cnae' => '6209100',
        'specialTaxRegime' => '1',
        'serviceListItem' => '17.02',
        'nbsCode' => '1.0101',
        'rpsSerie' => '1',
        'rpsNumber' => 12,
        'loteNumber' => 7,
        'username' => 'john',
        'password' => 'p4ss',
        'accessToken' => 'tok',
        'certificatePassword' => 'cert-p4ss',
        'nationalPortalTaxCalculationRegime' => '0',
    ]);

    expect($req->toArray())->toBe([
        'email' => 'a@b.c',
        'simplesNacional' => false,
        'municipalInscription' => '21779501',
        'culturalProjectsPromoter' => true,
        'cnae' => '6209100',
        'specialTaxRegime' => '1',
        'serviceListItem' => '17.02',
        'nbsCode' => '1.0101',
        'rpsSerie' => '1',
        'rpsNumber' => 12,
        'loteNumber' => 7,
        'username' => 'john',
        'password' => 'p4ss',
        'accessToken' => 'tok',
        'certificatePassword' => 'cert-p4ss',
        'nationalPortalTaxCalculationRegime' => '0',
    ]);
});

it('masks sensitive fields in __debugInfo', function (): void {
    $req = new FiscalInfoRequest(
        email: 'fiscal@asaas.com',
        username: 'john',
        password: 'p4ss',
        accessToken: 'tok',
        certificatePassword: 'cert-p4ss',
    );

    $debug = $req->__debugInfo();

    expect($debug['email'])->toBe('***');
    expect($debug['username'])->toBe('***');
    expect($debug['password'])->toBe('***');
    expect($debug['accessToken'])->toBe('***');
    expect($debug['certificatePassword'])->toBe('***');
});

it('omits Missing fields entirely from __debugInfo', function (): void {
    $req = new FiscalInfoRequest(email: 'fiscal@asaas.com');

    $debug = $req->__debugInfo();

    expect(array_keys($debug))->toBe(['email']);
    expect($debug)->not->toHaveKey('simplesNacional');
    expect($debug)->not->toHaveKey('password');
});

it('refuses to be serialised', function (): void {
    serialize(new FiscalInfoRequest(email: 'a@b.c'));
})->throws(LogicException::class);
