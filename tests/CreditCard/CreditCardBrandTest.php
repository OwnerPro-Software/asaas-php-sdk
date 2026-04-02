<?php

declare(strict_types=1);

use OwnerPro\Asaas\CreditCard\CreditCardBrand;

mutates(CreditCardBrand::class);

it('has Visa case', fn () => expect(CreditCardBrand::Visa->value)->toBe('VISA'));
it('has Mastercard case', fn () => expect(CreditCardBrand::Mastercard->value)->toBe('MASTERCARD'));
it('has Elo case', fn () => expect(CreditCardBrand::Elo->value)->toBe('ELO'));
it('has Diners case', fn () => expect(CreditCardBrand::Diners->value)->toBe('DINERS'));
it('has Discover case', fn () => expect(CreditCardBrand::Discover->value)->toBe('DISCOVER'));
it('has Amex case', fn () => expect(CreditCardBrand::Amex->value)->toBe('AMEX'));
it('has Cabal case', fn () => expect(CreditCardBrand::Cabal->value)->toBe('CABAL'));
it('has Banescard case', fn () => expect(CreditCardBrand::Banescard->value)->toBe('BANESCARD'));
it('has Credz case', fn () => expect(CreditCardBrand::Credz->value)->toBe('CREDZ'));
it('has Sorocred case', fn () => expect(CreditCardBrand::Sorocred->value)->toBe('SOROCRED'));
it('has Credsystem case', fn () => expect(CreditCardBrand::Credsystem->value)->toBe('CREDSYSTEM'));
it('has Jcb case', fn () => expect(CreditCardBrand::Jcb->value)->toBe('JCB'));
it('has Unknown case', fn () => expect(CreditCardBrand::Unknown->value)->toBe('UNKNOWN'));

it('creates from valid string', fn () => expect(CreditCardBrand::from('VISA'))->toBe(CreditCardBrand::Visa));

it('throws for invalid string', fn () => CreditCardBrand::from('INVALID'))->throws(ValueError::class);
