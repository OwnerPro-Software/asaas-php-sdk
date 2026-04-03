<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\Taxes;

mutates(Taxes::class);

it('creates from array with all fields', function (): void {
    $taxes = Taxes::fromArray([
        'retainIss' => true,
        'iss' => 2.0,
        'pis' => 0.65,
        'cofins' => 3.0,
        'csll' => 1.0,
        'inss' => 11.0,
        'ir' => 1.5,
        'nbsCode' => '1.0101',
        'taxSituationCode' => '01',
        'taxClassificationCode' => '1401',
        'operationIndicatorCode' => '01',
        'pisCofinsRetentionType' => 'RETAINED',
        'pisCofinsTaxStatus' => '01',
    ]);

    expect($taxes->retainIss)->toBeTrue();
    expect($taxes->iss)->toBe(2.0);
    expect($taxes->nbsCode)->toBe('1.0101');
    expect($taxes->taxSituationCode)->toBe('01');
    expect($taxes->taxClassificationCode)->toBe('1401');
    expect($taxes->operationIndicatorCode)->toBe('01');
    expect($taxes->pisCofinsRetentionType)->toBe('RETAINED');
    expect($taxes->pisCofinsTaxStatus)->toBe('01');
});

it('converts to array filtering nulls', function (): void {
    $taxes = new Taxes(
        retainIss: true,
        iss: 2.0,
        pis: 0.65,
        cofins: 3.0,
        csll: 1.0,
        inss: 11.0,
        ir: 1.5,
    );

    expect($taxes->toArray())->toBe([
        'retainIss' => true,
        'iss' => 2.0,
        'pis' => 0.65,
        'cofins' => 3.0,
        'csll' => 1.0,
        'inss' => 11.0,
        'ir' => 1.5,
    ]);
});

it('keeps false values in toArray', function (): void {
    $taxes = new Taxes(
        retainIss: false,
        iss: 0.0,
        pis: 0.0,
        cofins: 0.0,
        csll: 0.0,
        inss: 0.0,
        ir: 0.0,
    );

    expect($taxes->toArray())->toMatchArray(['retainIss' => false, 'iss' => 0.0]);
});

it('throws when required field is missing', function (string $missingField): void {
    $data = [
        'retainIss' => true,
        'iss' => 2.0,
        'pis' => 0.65,
        'cofins' => 3.0,
        'csll' => 1.0,
        'inss' => 11.0,
        'ir' => 1.5,
    ];

    unset($data[$missingField]);

    Taxes::fromArray($data);
})->throws(TypeError::class)->with([
    'retainIss',
    'iss',
    'pis',
    'cofins',
    'csll',
    'inss',
    'ir',
]);
