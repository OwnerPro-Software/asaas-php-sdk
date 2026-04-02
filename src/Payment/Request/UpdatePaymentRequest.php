<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class UpdatePaymentRequest
{
    use HasArrayFactory;

    /** @param list<array<string, mixed>|Split>|null $split */
    public function __construct(
        public BillingType|string|null $billingType = null,
        public ?float $value = null,
        public ?string $dueDate = null,
        public ?string $description = null,
        public ?string $externalReference = null,
        public ?float $discount = null,
        public ?float $interest = null,
        public ?float $fine = null,
        public ?bool $postalService = null,
        public ?array $split = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
