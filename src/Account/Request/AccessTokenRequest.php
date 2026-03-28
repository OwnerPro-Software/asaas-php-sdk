<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class AccessTokenRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $name = null,
        public ?bool $enabled = null,
        public ?string $expirationDate = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
