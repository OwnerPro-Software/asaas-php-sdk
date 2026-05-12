<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\DiscountType;

mutates(DiscountType::class);

it('exposes the documented discount type cases', function (): void {
    expect(array_map(fn (DiscountType $c): string => $c->value, DiscountType::cases()))->toBe([
        'FIXED', 'PERCENTAGE',
    ]);
});
