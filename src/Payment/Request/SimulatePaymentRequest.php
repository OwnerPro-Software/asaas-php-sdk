<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class SimulatePaymentRequest
{
    use HasArrayFactory;

    /** @param list<BillingType|string> $billingTypes */
    public function __construct(
        public float $value,
        public array $billingTypes,
        public ?int $installmentCount = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['value', 'billingTypes'];
    }
}
