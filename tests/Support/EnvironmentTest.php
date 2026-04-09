<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\Environment;

mutates(Environment::class);

it('has sandbox case with correct value', function (): void {
    expect(Environment::Sandbox->value)->toBe('sandbox');
});

it('has production case with correct value', function (): void {
    expect(Environment::Production->value)->toBe('production');
});

it('returns sandbox base url', function (): void {
    expect(Environment::Sandbox->baseUrl())->toBe('https://api-sandbox.asaas.com/v3');
});

it('returns production base url', function (): void {
    expect(Environment::Production->baseUrl())->toBe('https://api.asaas.com/v3');
});

it('creates from valid string', function (): void {
    expect(Environment::from('sandbox'))->toBe(Environment::Sandbox);
    expect(Environment::from('production'))->toBe(Environment::Production);
});

it('throws ValueError for invalid string', function (): void {
    Environment::from('staging');
})->throws(ValueError::class);
