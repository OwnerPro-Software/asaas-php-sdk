<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class AccessTokenRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly ?string $name = null,
        public readonly ?bool $enabled = null,
        public readonly ?string $expirationDate = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
