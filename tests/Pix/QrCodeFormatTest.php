<?php

declare(strict_types=1);

use OwnerPro\Asaas\Pix\QrCodeFormat;

mutates(QrCodeFormat::class);

it('has All case', fn () => expect(QrCodeFormat::All->value)->toBe('ALL'));
it('has Image case', fn () => expect(QrCodeFormat::Image->value)->toBe('IMAGE'));
it('has Payload case', fn () => expect(QrCodeFormat::Payload->value)->toBe('PAYLOAD'));

it('creates from valid string', fn () => expect(QrCodeFormat::from('ALL'))->toBe(QrCodeFormat::All));

it('throws for invalid string', fn () => QrCodeFormat::from('INVALID'))->throws(ValueError::class);
