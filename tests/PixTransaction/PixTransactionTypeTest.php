<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\PixTransactionType;

mutates(PixTransactionType::class);

it('has Debit case', fn () => expect(PixTransactionType::Debit->value)->toBe('DEBIT'));
it('has Credit case', fn () => expect(PixTransactionType::Credit->value)->toBe('CREDIT'));
it('has CreditRefund case', fn () => expect(PixTransactionType::CreditRefund->value)->toBe('CREDIT_REFUND'));
it('has DebitRefund case', fn () => expect(PixTransactionType::DebitRefund->value)->toBe('DEBIT_REFUND'));
it('has DebitRefundCancellation case', fn () => expect(PixTransactionType::DebitRefundCancellation->value)->toBe('DEBIT_REFUND_CANCELLATION'));

it('creates from valid string', fn () => expect(PixTransactionType::from('DEBIT'))->toBe(PixTransactionType::Debit));

it('throws for invalid string', fn () => PixTransactionType::from('INVALID'))->throws(ValueError::class);
