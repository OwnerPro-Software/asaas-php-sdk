<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class AccessTokenConfig implements Arrayable
{
    use HasArrayFactory;

    /** @var list<AccessTokenPermissionConfig>|null */
    public ?array $permissions;

    /** @param list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|AccessTokenPermissionConfig>|null $permissions */
    public function __construct(
        public ?string $name = null,
        ?array $permissions = null,
    ) {
        $this->permissions = $permissions !== null ? array_map(
            fn (array|AccessTokenPermissionConfig $item): AccessTokenPermissionConfig => $item instanceof AccessTokenPermissionConfig ? $item : AccessTokenPermissionConfig::fromArray($item),
            $permissions,
        ) : null;
    }

    /** @param array{name?: string, permissions?: list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|AccessTokenPermissionConfig>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? null,
            permissions: $data['permissions'] ?? null,
        );
    }
}
