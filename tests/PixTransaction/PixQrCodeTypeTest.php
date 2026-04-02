<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixTransaction\PixQrCodeType;

mutates(PixQrCodeType::class);

it('has Static case', fn () => expect(PixQrCodeType::Static->value)->toBe('STATIC'));
it('has Dynamic case', fn () => expect(PixQrCodeType::Dynamic->value)->toBe('DYNAMIC'));
it('has DynamicWithAsaasAddressKey case', fn () => expect(PixQrCodeType::DynamicWithAsaasAddressKey->value)->toBe('DYNAMIC_WITH_ASAAS_ADDRESS_KEY'));
it('has Composite case', fn () => expect(PixQrCodeType::Composite->value)->toBe('COMPOSITE'));

it('creates from valid string', fn () => expect(PixQrCodeType::from('STATIC'))->toBe(PixQrCodeType::Static));

it('throws for invalid string', fn () => PixQrCodeType::from('INVALID'))->throws(ValueError::class);
