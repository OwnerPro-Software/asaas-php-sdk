<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class Bank
{
    use HasArrayFactory;

    public function __construct(
        public string $code,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['code'];
    }
}
