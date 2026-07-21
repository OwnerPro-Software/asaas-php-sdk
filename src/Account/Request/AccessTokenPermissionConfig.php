<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class AccessTokenPermissionConfig implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public AccessTokenPermission|string $name,
        public AccessTokenScope|string $scope,
    ) {}

    /** @param array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? throw new InvalidArgumentException('AccessTokenPermissionConfig: name is required'),
            scope: $data['scope'] ?? throw new InvalidArgumentException('AccessTokenPermissionConfig: scope is required'),
        );
    }

    /**
     * Coerces the `permissions` list carried by every DTO that mints or
     * amends an API key.
     *
     * An empty list collapses to `null` so the key is omitted from the body:
     * omitting `permissions` mints the documented all-permissions `READ_WRITE`
     * key, whereas `{"permissions": []}` has no documented meaning and would
     * leave the key in an undefined permission state. `[]` is exactly what
     * Laravel's `$request->validated()` yields for an absent client-supplied
     * list, so it reaches these DTOs routinely.
     *
     * @param  list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|self>|null  $permissions
     * @return list<self>|null
     */
    public static function coerceList(?array $permissions): ?array
    {
        if ($permissions === null || $permissions === []) {
            return null;
        }

        return array_map(
            fn (array|self $item): self => $item instanceof self ? $item : self::fromArray($item),
            $permissions,
        );
    }
}
