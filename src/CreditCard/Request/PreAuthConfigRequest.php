<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class PreAuthConfigRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly int $daysToExpire,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['daysToExpire'];
    }
}
