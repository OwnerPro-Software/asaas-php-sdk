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

    /** @param array{billingType?: BillingType|string, value?: float, dueDate?: string, description?: string, externalReference?: string, discount?: float, interest?: float, fine?: float, postalService?: bool, split?: list<array<string, mixed>>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            billingType: $data['billingType'] ?? null,
            value: $data['value'] ?? null,
            dueDate: $data['dueDate'] ?? null,
            description: $data['description'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            discount: $data['discount'] ?? null,
            interest: $data['interest'] ?? null,
            fine: $data['fine'] ?? null,
            postalService: $data['postalService'] ?? null,
            split: $data['split'] ?? null,
        );
    }
}
