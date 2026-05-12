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
}
