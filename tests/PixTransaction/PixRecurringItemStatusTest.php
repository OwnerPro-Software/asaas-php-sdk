<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\PixRecurringItemStatus;

mutates(PixRecurringItemStatus::class);

it('has Pending case', fn () => expect(PixRecurringItemStatus::Pending->value)->toBe('PENDING'));
it('has Cancelled case', fn () => expect(PixRecurringItemStatus::Cancelled->value)->toBe('CANCELLED'));
it('has Refused case', fn () => expect(PixRecurringItemStatus::Refused->value)->toBe('REFUSED'));
it('has Done case', fn () => expect(PixRecurringItemStatus::Done->value)->toBe('DONE'));

it('creates from valid string', fn () => expect(PixRecurringItemStatus::from('DONE'))->toBe(PixRecurringItemStatus::Done));

it('throws for invalid string', fn () => PixRecurringItemStatus::from('INVALID'))->throws(ValueError::class);
