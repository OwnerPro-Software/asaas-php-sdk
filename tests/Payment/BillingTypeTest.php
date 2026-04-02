<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\BillingType;

mutates(BillingType::class);

it('has Undefined case', fn () => expect(BillingType::Undefined->value)->toBe('UNDEFINED'));
it('has Boleto case', fn () => expect(BillingType::Boleto->value)->toBe('BOLETO'));
it('has CreditCard case', fn () => expect(BillingType::CreditCard->value)->toBe('CREDIT_CARD'));
it('has DebitCard case', fn () => expect(BillingType::DebitCard->value)->toBe('DEBIT_CARD'));
it('has Transfer case', fn () => expect(BillingType::Transfer->value)->toBe('TRANSFER'));
it('has Deposit case', fn () => expect(BillingType::Deposit->value)->toBe('DEPOSIT'));
it('has Pix case', fn () => expect(BillingType::Pix->value)->toBe('PIX'));

it('creates from valid string', fn () => expect(BillingType::from('BOLETO'))->toBe(BillingType::Boleto));

it('throws for invalid string', fn () => BillingType::from('INVALID'))->throws(ValueError::class);
