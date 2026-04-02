<?php

declare(strict_types=1);

use OwnerPro\Asaas\Transfer\TransferStatus;

mutates(TransferStatus::class);

it('has Pending case', fn () => expect(TransferStatus::Pending->value)->toBe('PENDING'));
it('has BankProcessing case', fn () => expect(TransferStatus::BankProcessing->value)->toBe('BANK_PROCESSING'));
it('has Done case', fn () => expect(TransferStatus::Done->value)->toBe('DONE'));
it('has Cancelled case', fn () => expect(TransferStatus::Cancelled->value)->toBe('CANCELLED'));
it('has Failed case', fn () => expect(TransferStatus::Failed->value)->toBe('FAILED'));

it('creates from valid string', fn () => expect(TransferStatus::from('PENDING'))->toBe(TransferStatus::Pending));

it('throws for invalid string', fn () => TransferStatus::from('INVALID'))->throws(ValueError::class);
