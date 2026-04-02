<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\PersonType;

mutates(PersonType::class);

it('has Fisica case', fn () => expect(PersonType::Fisica->value)->toBe('FISICA'));
it('has Juridica case', fn () => expect(PersonType::Juridica->value)->toBe('JURIDICA'));

it('creates from valid string', fn () => expect(PersonType::from('FISICA'))->toBe(PersonType::Fisica));

it('throws for invalid string', fn () => PersonType::from('INVALID'))->throws(ValueError::class);
