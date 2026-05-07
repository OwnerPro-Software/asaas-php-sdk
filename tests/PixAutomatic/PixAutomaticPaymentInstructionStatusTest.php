<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixAutomatic\PixAutomaticPaymentInstructionStatus;

mutates(PixAutomaticPaymentInstructionStatus::class);

it('has AwaitingRequest case', fn () => expect(PixAutomaticPaymentInstructionStatus::AwaitingRequest->value)->toBe('AWAITING_REQUEST'));
it('has Scheduled case', fn () => expect(PixAutomaticPaymentInstructionStatus::Scheduled->value)->toBe('SCHEDULED'));
it('has Done case', fn () => expect(PixAutomaticPaymentInstructionStatus::Done->value)->toBe('DONE'));
it('has Cancelled case', fn () => expect(PixAutomaticPaymentInstructionStatus::Cancelled->value)->toBe('CANCELLED'));
it('has Refused case', fn () => expect(PixAutomaticPaymentInstructionStatus::Refused->value)->toBe('REFUSED'));

it('creates from valid string', fn () => expect(PixAutomaticPaymentInstructionStatus::from('SCHEDULED'))->toBe(PixAutomaticPaymentInstructionStatus::Scheduled));

it('throws for invalid string', fn () => PixAutomaticPaymentInstructionStatus::from('INVALID'))->throws(ValueError::class);
