<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\CreditCard;

mutates(CreditCard::class);

it('creates from array', function (): void {
    $card = CreditCard::fromArray([
        'holderName' => 'John Doe',
        'number' => '4111111111111111',
        'expiryMonth' => '12',
        'expiryYear' => '2030',
        'ccv' => '123',
    ]);

    expect($card->holderName)->toBe('John Doe');
    expect($card->number)->toBe('4111111111111111');
    expect($card->expiryMonth)->toBe('12');
    expect($card->expiryYear)->toBe('2030');
    expect($card->ccv)->toBe('123');
});

it('converts to array', function (): void {
    $card = new CreditCard(
        holderName: 'John Doe',
        number: '4111111111111111',
        expiryMonth: '12',
        expiryYear: '2030',
        ccv: '123',
    );

    expect($card->toArray())->toBe([
        'holderName' => 'John Doe',
        'number' => '4111111111111111',
        'expiryMonth' => '12',
        'expiryYear' => '2030',
        'ccv' => '123',
    ]);
});

it('masks sensitive data in debug info', function (): void {
    $card = new CreditCard(
        holderName: 'John Doe',
        number: '4111111111111111',
        expiryMonth: '12',
        expiryYear: '2030',
        ccv: '123',
    );

    $debug = $card->__debugInfo();

    expect($debug['holderName'])->toBe('John Doe');
    expect($debug['number'])->toBe('************1111');
    expect($debug['expiryMonth'])->toBe('12');
    expect($debug['expiryYear'])->toBe('2030');
    expect($debug['ccv'])->toBe('***');
});

it('masks short card number in debug info', function (): void {
    $card = new CreditCard(
        holderName: 'John Doe',
        number: '12',
        expiryMonth: '12',
        expiryYear: '2030',
        ccv: '123',
    );

    expect($card->__debugInfo()['number'])->toBe('12');
});

it('throws when required field is missing', function (string $missingField): void {
    $data = [
        'holderName' => 'John Doe',
        'number' => '4111111111111111',
        'expiryMonth' => '12',
        'expiryYear' => '2030',
        'ccv' => '123',
    ];

    unset($data[$missingField]);

    CreditCard::fromArray($data);
})->throws(InvalidArgumentException::class)->with([
    'holderName',
    'number',
    'expiryMonth',
    'expiryYear',
    'ccv',
]);
