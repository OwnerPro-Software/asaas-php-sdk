<?php

declare(strict_types=1);

use OwnerPro\Asaas\Pix\PixAddressKeyStatus;

mutates(PixAddressKeyStatus::class);

it('has AwaitingActivation case', fn () => expect(PixAddressKeyStatus::AwaitingActivation->value)->toBe('AWAITING_ACTIVATION'));
it('has Active case', fn () => expect(PixAddressKeyStatus::Active->value)->toBe('ACTIVE'));
it('has AwaitingDeletion case', fn () => expect(PixAddressKeyStatus::AwaitingDeletion->value)->toBe('AWAITING_DELETION'));
it('has AwaitingAccountDeletion case', fn () => expect(PixAddressKeyStatus::AwaitingAccountDeletion->value)->toBe('AWAITING_ACCOUNT_DELETION'));
it('has Deleted case', fn () => expect(PixAddressKeyStatus::Deleted->value)->toBe('DELETED'));
it('has Error case', fn () => expect(PixAddressKeyStatus::Error->value)->toBe('ERROR'));

it('creates from valid string', fn () => expect(PixAddressKeyStatus::from('ACTIVE'))->toBe(PixAddressKeyStatus::Active));

it('throws for invalid string', fn () => PixAddressKeyStatus::from('INVALID'))->throws(ValueError::class);
