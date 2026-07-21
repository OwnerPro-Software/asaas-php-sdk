<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreateAccessTokenRequest
{
    use HasArrayFactory;

    /** @var list<AccessTokenPermissionConfig>|null */
    public ?array $permissions;

    /**
     * @param  list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|AccessTokenPermissionConfig>|null  $permissions
     * @param  string|null  $expirationDate  Format `YYYY-MM-DD HH:MM:SS`.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $expirationDate = null,
        ?array $permissions = null,
    ) {
        $this->permissions = AccessTokenPermissionConfig::coerceList($permissions);
    }

    /** @param array{name?: string, expirationDate?: string, permissions?: list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|AccessTokenPermissionConfig>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? null,
            expirationDate: $data['expirationDate'] ?? null,
            permissions: $data['permissions'] ?? null,
        );
    }
}
