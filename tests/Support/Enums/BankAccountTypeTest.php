<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\Enums\BankAccountType;

mutates(BankAccountType::class);

it('has ContaCorrente case', fn () => expect(BankAccountType::ContaCorrente->value)->toBe('CONTA_CORRENTE'));
it('has ContaPoupanca case', fn () => expect(BankAccountType::ContaPoupanca->value)->toBe('CONTA_POUPANCA'));

it('creates from valid string', fn () => expect(BankAccountType::from('CONTA_CORRENTE'))->toBe(BankAccountType::ContaCorrente));

it('throws for invalid string', fn () => BankAccountType::from('INVALID'))->throws(ValueError::class);
