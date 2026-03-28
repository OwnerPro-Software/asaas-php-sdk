<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class ReceivePaymentInCashRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $paymentDate = null,
        public ?float $value = null,
        public ?bool $notifyCustomer = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
