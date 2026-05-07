<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\PixRecurringStatus;

mutates(PixRecurringStatus::class);

it('has AwaitingCriticalActionAuthorization case', fn () => expect(PixRecurringStatus::AwaitingCriticalActionAuthorization->value)->toBe('AWAITING_CRITICAL_ACTION_AUTHORIZATION'));
it('has Pending case', fn () => expect(PixRecurringStatus::Pending->value)->toBe('PENDING'));
it('has Scheduled case', fn () => expect(PixRecurringStatus::Scheduled->value)->toBe('SCHEDULED'));
it('has Cancelled case', fn () => expect(PixRecurringStatus::Cancelled->value)->toBe('CANCELLED'));
it('has Done case', fn () => expect(PixRecurringStatus::Done->value)->toBe('DONE'));

it('creates from valid string', fn () => expect(PixRecurringStatus::from('PENDING'))->toBe(PixRecurringStatus::Pending));

it('throws for invalid string', fn () => PixRecurringStatus::from('INVALID'))->throws(ValueError::class);
