<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\AccessTokenScope;

mutates(AccessTokenScope::class);

it('exposes the documented scope cases', function (): void {
    expect(array_map(fn (AccessTokenScope $c): string => $c->value, AccessTokenScope::cases()))->toBe([
        'READ', 'READ_WRITE',
    ]);
});
