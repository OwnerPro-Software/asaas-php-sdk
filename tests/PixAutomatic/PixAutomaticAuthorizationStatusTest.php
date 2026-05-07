<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixAutomatic\PixAutomaticAuthorizationStatus;

mutates(PixAutomaticAuthorizationStatus::class);

it('has Created case', fn () => expect(PixAutomaticAuthorizationStatus::Created->value)->toBe('CREATED'));
it('has Active case', fn () => expect(PixAutomaticAuthorizationStatus::Active->value)->toBe('ACTIVE'));
it('has Cancelled case', fn () => expect(PixAutomaticAuthorizationStatus::Cancelled->value)->toBe('CANCELLED'));
it('has Refused case', fn () => expect(PixAutomaticAuthorizationStatus::Refused->value)->toBe('REFUSED'));
it('has Expired case', fn () => expect(PixAutomaticAuthorizationStatus::Expired->value)->toBe('EXPIRED'));

it('creates from valid string', fn () => expect(PixAutomaticAuthorizationStatus::from('ACTIVE'))->toBe(PixAutomaticAuthorizationStatus::Active));

it('throws for invalid string', fn () => PixAutomaticAuthorizationStatus::from('INVALID'))->throws(ValueError::class);
