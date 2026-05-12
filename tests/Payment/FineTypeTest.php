<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\FineType;

mutates(FineType::class);

it('exposes the documented fine type cases', function (): void {
    expect(array_map(fn (FineType $c): string => $c->value, FineType::cases()))->toBe([
        'FIXED', 'PERCENTAGE',
    ]);
});
