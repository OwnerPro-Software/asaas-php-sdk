<?php

declare(strict_types=1);

use OwnerPro\Asaas\Transfer\TransferOperationType;

mutates(TransferOperationType::class);

it('has Pix case', fn () => expect(TransferOperationType::Pix->value)->toBe('PIX'));
it('has Ted case', fn () => expect(TransferOperationType::Ted->value)->toBe('TED'));
it('has Internal case', fn () => expect(TransferOperationType::Internal->value)->toBe('INTERNAL'));

it('creates from valid string', fn () => expect(TransferOperationType::from('PIX'))->toBe(TransferOperationType::Pix));

it('throws for invalid string', fn () => TransferOperationType::from('INVALID'))->throws(ValueError::class);
