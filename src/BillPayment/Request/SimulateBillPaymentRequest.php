<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class SimulateBillPaymentRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly ?string $identificationField = null,
        public readonly ?string $barCode = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
