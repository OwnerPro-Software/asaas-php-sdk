<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

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

    /** @param array{customer?: string, billingType?: BillingType|string, value?: float, dueDate?: string, description?: string, externalReference?: string, discount?: float, interest?: float, fine?: float, postalService?: bool, split?: list<array<string, mixed>>, callback?: array{successUrl?: string, autoRedirect?: bool}, creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}, remoteIp?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            customer: $data['customer'] ?? throw new TypeError('customer is required'),
            billingType: $data['billingType'] ?? throw new TypeError('billingType is required'),
            value: $data['value'] ?? throw new TypeError('value is required'),
            dueDate: $data['dueDate'] ?? throw new TypeError('dueDate is required'),
            description: $data['description'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            discount: $data['discount'] ?? null,
            interest: $data['interest'] ?? null,
            fine: $data['fine'] ?? null,
            postalService: $data['postalService'] ?? null,
            split: $data['split'] ?? null,
            callback: isset($data['callback']) ? Callback::fromArray($data['callback']) : null,
            creditCard: isset($data['creditCard']) ? CreditCard::fromArray($data['creditCard']) : null,
            creditCardHolderInfo: isset($data['creditCardHolderInfo']) ? CreditCardHolderInfo::fromArray($data['creditCardHolderInfo']) : null,
            remoteIp: $data['remoteIp'] ?? null,
        );
    }
}
