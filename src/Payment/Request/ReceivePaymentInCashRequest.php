<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class ReceivePaymentInCashRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly ?string $paymentDate = null,
        public readonly ?float $value = null,
        public readonly ?bool $notifyCustomer = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
