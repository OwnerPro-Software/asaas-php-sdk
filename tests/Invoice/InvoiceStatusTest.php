<?php

declare(strict_types=1);

use OwnerPro\Asaas\Invoice\InvoiceStatus;

mutates(InvoiceStatus::class);

it('has Scheduled case', fn () => expect(InvoiceStatus::Scheduled->value)->toBe('SCHEDULED'));
it('has Authorized case', fn () => expect(InvoiceStatus::Authorized->value)->toBe('AUTHORIZED'));
it('has ProcessingCancellation case', fn () => expect(InvoiceStatus::ProcessingCancellation->value)->toBe('PROCESSING_CANCELLATION'));
it('has Canceled case', fn () => expect(InvoiceStatus::Canceled->value)->toBe('CANCELED'));
it('has CancellationDenied case', fn () => expect(InvoiceStatus::CancellationDenied->value)->toBe('CANCELLATION_DENIED'));
it('has Error case', fn () => expect(InvoiceStatus::Error->value)->toBe('ERROR'));

it('creates from valid string', fn () => expect(InvoiceStatus::from('SCHEDULED'))->toBe(InvoiceStatus::Scheduled));

it('throws for invalid string', fn () => InvoiceStatus::from('INVALID'))->throws(ValueError::class);
