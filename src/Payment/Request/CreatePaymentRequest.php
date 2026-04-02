<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreatePaymentRequest
{
    use HasArrayFactory;

    /**
     * @param  list<array<string, mixed>|Split>|null  $split
     * @param  array<string, mixed>|Callback|null  $callback
     * @param  array<string, mixed>|CreditCard|null  $creditCard
     * @param  array<string, mixed>|CreditCardHolderInfo|null  $creditCardHolderInfo
     */
    public function __construct(
        public string $customer,
        public BillingType|string $billingType,
        public float $value,
        public string $dueDate,
        public ?string $description = null,
        public ?string $externalReference = null,
        public ?float $discount = null,
        public ?float $interest = null,
        public ?float $fine = null,
        public ?bool $postalService = null,
        public ?array $split = null,
        public array|Callback|null $callback = null,
        public array|CreditCard|null $creditCard = null,
        public array|CreditCardHolderInfo|null $creditCardHolderInfo = null,
        public ?string $remoteIp = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['customer', 'billingType', 'value', 'dueDate'];
    }
}
