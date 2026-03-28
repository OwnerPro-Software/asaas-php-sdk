<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class SimulateBillPaymentRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $identificationField = null,
        public ?string $barCode = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
