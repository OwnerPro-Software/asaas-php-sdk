<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\Bank;

mutates(Bank::class);

it('creates from array', function (): void {
    $bank = Bank::fromArray(['code' => '001']);

    expect($bank->code)->toBe('001');
});

it('converts to array', function (): void {
    $bank = new Bank(code: '001');

    expect($bank->toArray())->toBe(['code' => '001']);
});

it('throws when code is missing', function (): void {
    Bank::fromArray([]);
})->throws(InvalidArgumentException::class, "Field 'code' is required.");
