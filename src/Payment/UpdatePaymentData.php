<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class UpdatePaymentData
{
    use HasArrayFactory;

    /** @param list<array<string, mixed>>|null $split */
    public function __construct(
        public readonly ?string $billingType = null,
        public readonly ?float $value = null,
        public readonly ?string $dueDate = null,
        public readonly ?string $description = null,
        public readonly ?string $externalReference = null,
        public readonly ?float $discount = null,
        public readonly ?float $interest = null,
        public readonly ?float $fine = null,
        public readonly ?bool $postalService = null,
        public readonly ?array $split = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
