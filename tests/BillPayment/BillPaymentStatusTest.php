<?php

declare(strict_types=1);

use OwnerPro\Asaas\BillPayment\BillPaymentStatus;

mutates(BillPaymentStatus::class);

it('has Pending case', fn () => expect(BillPaymentStatus::Pending->value)->toBe('PENDING'));
it('has BankProcessing case', fn () => expect(BillPaymentStatus::BankProcessing->value)->toBe('BANK_PROCESSING'));
it('has Paid case', fn () => expect(BillPaymentStatus::Paid->value)->toBe('PAID'));
it('has Failed case', fn () => expect(BillPaymentStatus::Failed->value)->toBe('FAILED'));
it('has Cancelled case', fn () => expect(BillPaymentStatus::Cancelled->value)->toBe('CANCELLED'));
it('has Refunded case', fn () => expect(BillPaymentStatus::Refunded->value)->toBe('REFUNDED'));
it('has AwaitingCheckoutRiskAnalysisRequest case', fn () => expect(BillPaymentStatus::AwaitingCheckoutRiskAnalysisRequest->value)->toBe('AWAITING_CHECKOUT_RISK_ANALYSIS_REQUEST'));

it('creates from valid string', fn () => expect(BillPaymentStatus::from('PENDING'))->toBe(BillPaymentStatus::Pending));

it('throws for invalid string', fn () => BillPaymentStatus::from('INVALID'))->throws(ValueError::class);
