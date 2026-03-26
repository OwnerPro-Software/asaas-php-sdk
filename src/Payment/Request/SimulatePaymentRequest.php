<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class SimulatePaymentRequest
{
    use HasArrayFactory;

    /** @param list<string> $billingTypes */
    public function __construct(
        public readonly float $value,
        public readonly array $billingTypes,
        public readonly ?int $installmentCount = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['value', 'billingTypes'];
    }
}
