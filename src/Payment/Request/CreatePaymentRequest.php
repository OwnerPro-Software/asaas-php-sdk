<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreatePaymentRequest
{
    use HasArrayFactory;

    /**
     * @param  list<array<string, mixed>>|null  $split
     * @param  array<string, mixed>|null  $callback
     * @param  array<string, mixed>|null  $creditCard
     * @param  array<string, mixed>|null  $creditCardHolderInfo
     */
    public function __construct(
        public readonly string $customer,
        public readonly string $billingType,
        public readonly float $value,
        public readonly string $dueDate,
        public readonly ?string $description = null,
        public readonly ?string $externalReference = null,
        public readonly ?float $discount = null,
        public readonly ?float $interest = null,
        public readonly ?float $fine = null,
        public readonly ?bool $postalService = null,
        public readonly ?array $split = null,
        public readonly ?array $callback = null,
        public readonly ?array $creditCard = null,
        public readonly ?array $creditCardHolderInfo = null,
        public readonly ?string $remoteIp = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['customer', 'billingType', 'value', 'dueDate'];
    }
}
