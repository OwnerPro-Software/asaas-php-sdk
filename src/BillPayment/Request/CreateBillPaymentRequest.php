<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreateBillPaymentRequest
{
    use HasArrayFactory;

    public function __construct(
        public string $identificationField,
        public ?string $scheduleDate = null,
        public ?string $description = null,
        public ?float $discount = null,
        public ?float $interest = null,
        public ?float $fine = null,
        public ?string $dueDate = null,
        public ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['identificationField'];
    }
}
