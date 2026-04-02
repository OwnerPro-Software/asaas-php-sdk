<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Request;

use OwnerPro\Asaas\Pix\PixAddressKeyType;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class PixKeyRequest
{
    use HasArrayFactory;

    public function __construct(
        public PixAddressKeyType|string $type,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['type'];
    }
}
