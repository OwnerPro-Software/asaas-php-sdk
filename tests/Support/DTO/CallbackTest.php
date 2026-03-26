<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\DTO\Callback;

mutates(Callback::class);

it('creates from array with all fields', function (): void {
    $callback = Callback::fromArray(['successUrl' => 'https://example.com', 'autoRedirect' => false]);

    expect($callback->successUrl)->toBe('https://example.com');
    expect($callback->autoRedirect)->toBeFalse();
});

it('converts to array filtering nulls', function (): void {
    $callback = new Callback(successUrl: 'https://example.com');

    expect($callback->toArray())->toBe(['successUrl' => 'https://example.com']);
});

it('throws when successUrl is missing', function (): void {
    Callback::fromArray([]);
})->throws(InvalidArgumentException::class, "Field 'successUrl' is required.");
