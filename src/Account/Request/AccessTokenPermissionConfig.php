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
     * `null` — the absent key — omits `permissions` from the body, which is the
     * documented way to mint an all-permissions `READ_WRITE` key.
     *
     * An explicit `[]` is **rejected** rather than folded into that omission.
     * The two inputs mean opposite things: a caller writing `permissions: []`
     * is asking for a key with no privileges, and silently omitting the key
     * would hand them the most privileged one Asaas issues. `{"permissions": []}`
     * has no documented meaning either, so there is no third option that both
     * reaches the wire and behaves — the caller has to say which one they meant.
     *
     * @param  list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|self>|null  $permissions
     * @return list<self>|null
     *
     * @throws InvalidArgumentException when handed an explicitly empty list
     */
    public static function coerceList(?array $permissions): ?array
    {
        if ($permissions === []) {
            throw new InvalidArgumentException(
                'An empty permissions list is ambiguous: omitting the key mints a key with ALL permissions (READ_WRITE), which is the opposite of what an empty list reads as. Pass at least one AccessTokenPermissionConfig to scope the key, or omit `permissions` entirely to accept the all-permissions default.',
            );
        }

        if ($permissions === null) {
            return null;
        }

        return array_map(
            fn (array|self $item): self => $item instanceof self ? $item : self::fromArray($item),
            $permissions,
        );
    }
}
