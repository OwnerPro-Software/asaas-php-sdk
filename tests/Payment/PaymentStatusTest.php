<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\PaymentStatus;

mutates(PaymentStatus::class);

it('has Pending case', fn () => expect(PaymentStatus::Pending->value)->toBe('PENDING'));
it('has Received case', fn () => expect(PaymentStatus::Received->value)->toBe('RECEIVED'));
it('has Confirmed case', fn () => expect(PaymentStatus::Confirmed->value)->toBe('CONFIRMED'));
it('has Overdue case', fn () => expect(PaymentStatus::Overdue->value)->toBe('OVERDUE'));
it('has Refunded case', fn () => expect(PaymentStatus::Refunded->value)->toBe('REFUNDED'));
it('has ReceivedInCash case', fn () => expect(PaymentStatus::ReceivedInCash->value)->toBe('RECEIVED_IN_CASH'));
it('has RefundRequested case', fn () => expect(PaymentStatus::RefundRequested->value)->toBe('REFUND_REQUESTED'));
it('has RefundInProgress case', fn () => expect(PaymentStatus::RefundInProgress->value)->toBe('REFUND_IN_PROGRESS'));
it('has ChargebackRequested case', fn () => expect(PaymentStatus::ChargebackRequested->value)->toBe('CHARGEBACK_REQUESTED'));
it('has ChargebackDispute case', fn () => expect(PaymentStatus::ChargebackDispute->value)->toBe('CHARGEBACK_DISPUTE'));
it('has AwaitingChargebackReversal case', fn () => expect(PaymentStatus::AwaitingChargebackReversal->value)->toBe('AWAITING_CHARGEBACK_REVERSAL'));
it('has DunningRequested case', fn () => expect(PaymentStatus::DunningRequested->value)->toBe('DUNNING_REQUESTED'));
it('has DunningReceived case', fn () => expect(PaymentStatus::DunningReceived->value)->toBe('DUNNING_RECEIVED'));
it('has AwaitingRiskAnalysis case', fn () => expect(PaymentStatus::AwaitingRiskAnalysis->value)->toBe('AWAITING_RISK_ANALYSIS'));

it('creates from valid string', fn () => expect(PaymentStatus::from('PENDING'))->toBe(PaymentStatus::Pending));

it('throws for invalid string', fn () => PaymentStatus::from('INVALID'))->throws(ValueError::class);
