<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\Enums\BankAccountType;

mutates(BankAccountType::class);

it('has CheckingAccount case', fn () => expect(BankAccountType::CheckingAccount->value)->toBe('CONTA_CORRENTE'));
it('has SavingsAccount case', fn () => expect(BankAccountType::SavingsAccount->value)->toBe('CONTA_POUPANCA'));

it('creates from valid string', fn () => expect(BankAccountType::from('CONTA_CORRENTE'))->toBe(BankAccountType::CheckingAccount));

it('throws for invalid string', fn () => BankAccountType::from('INVALID'))->throws(ValueError::class);
