<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\CompanyType;

mutates(CompanyType::class);

it('has Mei case', fn () => expect(CompanyType::Mei->value)->toBe('MEI'));
it('has Limited case', fn () => expect(CompanyType::Limited->value)->toBe('LIMITED'));
it('has Individual case', fn () => expect(CompanyType::Individual->value)->toBe('INDIVIDUAL'));
it('has Association case', fn () => expect(CompanyType::Association->value)->toBe('ASSOCIATION'));

it('creates from valid string', fn () => expect(CompanyType::from('MEI'))->toBe(CompanyType::Mei));

it('throws for invalid string', fn () => CompanyType::from('INVALID'))->throws(ValueError::class);
