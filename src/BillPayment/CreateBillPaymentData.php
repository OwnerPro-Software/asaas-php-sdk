<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateBillPaymentData
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $identificationField,
        public readonly ?string $scheduleDate = null,
        public readonly ?string $description = null,
        public readonly ?float $discount = null,
        public readonly ?float $interest = null,
        public readonly ?float $fine = null,
        public readonly ?string $dueDate = null,
        public readonly ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['identificationField'];
    }
}
