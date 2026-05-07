<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixAutomatic\PixAutomaticFrequency;

mutates(PixAutomaticFrequency::class);

it('has Weekly case', fn () => expect(PixAutomaticFrequency::Weekly->value)->toBe('WEEKLY'));
it('has Monthly case', fn () => expect(PixAutomaticFrequency::Monthly->value)->toBe('MONTHLY'));
it('has Quarterly case', fn () => expect(PixAutomaticFrequency::Quarterly->value)->toBe('QUARTERLY'));
it('has Semiannually case', fn () => expect(PixAutomaticFrequency::Semiannually->value)->toBe('SEMIANNUALLY'));
it('has Annually case', fn () => expect(PixAutomaticFrequency::Annually->value)->toBe('ANNUALLY'));

it('creates from valid string', fn () => expect(PixAutomaticFrequency::from('MONTHLY'))->toBe(PixAutomaticFrequency::Monthly));

it('throws for invalid string', fn () => PixAutomaticFrequency::from('INVALID'))->throws(ValueError::class);
