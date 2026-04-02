<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\PixTransactionStatus;

mutates(PixTransactionStatus::class);

it('has AwaitingBalanceValidation case', fn () => expect(PixTransactionStatus::AwaitingBalanceValidation->value)->toBe('AWAITING_BALANCE_VALIDATION'));
it('has AwaitingInstantPaymentAccountBalance case', fn () => expect(PixTransactionStatus::AwaitingInstantPaymentAccountBalance->value)->toBe('AWAITING_INSTANT_PAYMENT_ACCOUNT_BALANCE'));
it('has AwaitingCriticalActionAuthorization case', fn () => expect(PixTransactionStatus::AwaitingCriticalActionAuthorization->value)->toBe('AWAITING_CRITICAL_ACTION_AUTHORIZATION'));
it('has AwaitingCheckoutRiskAnalysisRequest case', fn () => expect(PixTransactionStatus::AwaitingCheckoutRiskAnalysisRequest->value)->toBe('AWAITING_CHECKOUT_RISK_ANALYSIS_REQUEST'));
it('has AwaitingCashInRiskAnalysisRequest case', fn () => expect(PixTransactionStatus::AwaitingCashInRiskAnalysisRequest->value)->toBe('AWAITING_CASH_IN_RISK_ANALYSIS_REQUEST'));
it('has Scheduled case', fn () => expect(PixTransactionStatus::Scheduled->value)->toBe('SCHEDULED'));
it('has AwaitingRequest case', fn () => expect(PixTransactionStatus::AwaitingRequest->value)->toBe('AWAITING_REQUEST'));
it('has Requested case', fn () => expect(PixTransactionStatus::Requested->value)->toBe('REQUESTED'));
it('has Done case', fn () => expect(PixTransactionStatus::Done->value)->toBe('DONE'));
it('has Refused case', fn () => expect(PixTransactionStatus::Refused->value)->toBe('REFUSED'));
it('has Cancelled case', fn () => expect(PixTransactionStatus::Cancelled->value)->toBe('CANCELLED'));

it('creates from valid string', fn () => expect(PixTransactionStatus::from('DONE'))->toBe(PixTransactionStatus::Done));

it('throws for invalid string', fn () => PixTransactionStatus::from('INVALID'))->throws(ValueError::class);
