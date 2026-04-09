<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreatePaymentRequest
{
    use HasArrayFactory;

    /** @var list<Split>|null */
    public ?array $split;

    public ?Callback $callback;

    public ?CreditCard $creditCard;

    public ?CreditCardHolderInfo $creditCardHolderInfo;

    /**
     * @param  list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}|Split>|null  $split
     * @param  array{successUrl?: string, autoRedirect?: bool}|Callback|null  $callback
     * @param  array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}|CreditCard|null  $creditCard
     * @param  array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}|CreditCardHolderInfo|null  $creditCardHolderInfo
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
        ?array $split = null,
        array|Callback|null $callback = null,
        array|CreditCard|null $creditCard = null,
        array|CreditCardHolderInfo|null $creditCardHolderInfo = null,
        public ?string $remoteIp = null,
    ) {
        $this->split = $split !== null ? array_map(
            fn (array|Split $item): Split => $item instanceof Split ? $item : Split::fromArray($item),
            $split,
        ) : null;
        $this->callback = is_array($callback) ? Callback::fromArray($callback) : $callback;
        $this->creditCard = is_array($creditCard) ? CreditCard::fromArray($creditCard) : $creditCard;
        $this->creditCardHolderInfo = is_array($creditCardHolderInfo) ? CreditCardHolderInfo::fromArray($creditCardHolderInfo) : $creditCardHolderInfo;
    }

    /** @param array{customer?: string, billingType?: BillingType|string, value?: float, dueDate?: string, description?: string, externalReference?: string, discount?: float, interest?: float, fine?: float, postalService?: bool, split?: list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}>, callback?: array{successUrl?: string, autoRedirect?: bool}, creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}, remoteIp?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            customer: $data['customer'] ?? throw new InvalidArgumentException('CreatePaymentRequest: customer is required'),
            billingType: $data['billingType'] ?? throw new InvalidArgumentException('CreatePaymentRequest: billingType is required'),
            value: $data['value'] ?? throw new InvalidArgumentException('CreatePaymentRequest: value is required'),
            dueDate: $data['dueDate'] ?? throw new InvalidArgumentException('CreatePaymentRequest: dueDate is required'),
            description: $data['description'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            discount: $data['discount'] ?? null,
            interest: $data['interest'] ?? null,
            fine: $data['fine'] ?? null,
            postalService: $data['postalService'] ?? null,
            split: isset($data['split']) ? array_map(
                Split::fromArray(...),
                $data['split'],
            ) : null,
            callback: isset($data['callback']) ? Callback::fromArray($data['callback']) : null,
            creditCard: isset($data['creditCard']) ? CreditCard::fromArray($data['creditCard']) : null,
            creditCardHolderInfo: isset($data['creditCardHolderInfo']) ? CreditCardHolderInfo::fromArray($data['creditCardHolderInfo']) : null,
            remoteIp: $data['remoteIp'] ?? null,
        );
    }
}
