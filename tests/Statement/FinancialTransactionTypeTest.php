<?php

declare(strict_types=1);

use OwnerPro\Asaas\Statement\FinancialTransactionType;

mutates(FinancialTransactionType::class);

dataset('financial_transaction_types', array_map(
    fn (FinancialTransactionType $case): array => [$case->name, $case->value],
    FinancialTransactionType::cases(),
));

it('has the correct value for each case', function (string $name, string $value): void {
    $case = FinancialTransactionType::from($value);

    expect($case->name)->toBe($name);
    expect($case->value)->toBe($value);
})->with('financial_transaction_types');

it('has 129 cases', fn () => expect(FinancialTransactionType::cases())->toHaveCount(129));

it('throws for invalid string', fn () => FinancialTransactionType::from('INVALID'))->throws(ValueError::class);
