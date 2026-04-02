<?php

declare(strict_types=1);

use OwnerPro\Asaas\Pix\PixAddressKeyType;

mutates(PixAddressKeyType::class);

it('has Cpf case', fn () => expect(PixAddressKeyType::Cpf->value)->toBe('CPF'));
it('has Cnpj case', fn () => expect(PixAddressKeyType::Cnpj->value)->toBe('CNPJ'));
it('has Email case', fn () => expect(PixAddressKeyType::Email->value)->toBe('EMAIL'));
it('has Phone case', fn () => expect(PixAddressKeyType::Phone->value)->toBe('PHONE'));
it('has Evp case', fn () => expect(PixAddressKeyType::Evp->value)->toBe('EVP'));

it('creates from valid string', fn () => expect(PixAddressKeyType::from('CPF'))->toBe(PixAddressKeyType::Cpf));

it('throws for invalid string', fn () => PixAddressKeyType::from('INVALID'))->throws(ValueError::class);
