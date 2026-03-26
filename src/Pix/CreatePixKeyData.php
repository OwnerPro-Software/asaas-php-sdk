<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreatePixKeyData
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $type,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['type'];
    }
}
