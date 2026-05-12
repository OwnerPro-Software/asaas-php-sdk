<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\DTO\Interest;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class CreatePaymentRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    /** @var list<Split>|null */
    public ?array $split;

    public ?Callback $callback;

    public ?CreditCard $creditCard;

    public ?CreditCardHolderInfo $creditCardHolderInfo;

    public ?Discount $discount;

    public ?Interest $interest;

    public ?Fine $fine;

    /**
     * @param  list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}|Split>|null  $split
     * @param  array{successUrl?: string, autoRedirect?: bool}|Callback|null  $callback
     * @param  array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}|CreditCard|null  $creditCard
     * @param  array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}|CreditCardHolderInfo|null  $creditCardHolderInfo
     * @param  array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float|null  $discount
     * @param  array{value?: float}|Interest|float|null  $interest
     * @param  array{value?: float, type?: FineType|string}|Fine|float|null  $fine
     * @param  string  $dueDate  Format `YYYY-MM-DD`.
     */
    public function __construct(
        public string $customer,
        public BillingType|string $billingType,
        public float $value,
        public string $dueDate,
        public ?string $description = null,
        public ?string $externalReference = null,
        array|Discount|float|null $discount = null,
        array|Interest|float|null $interest = null,
        array|Fine|float|null $fine = null,
        public ?bool $postalService = null,
        public ?int $daysAfterDueDateToRegistrationCancellation = null,
        public ?int $installmentCount = null,
        public ?float $installmentValue = null,
        public ?float $totalValue = null,
        public ?string $pixAutomaticAuthorizationId = null,
        ?array $split = null,
        array|Callback|null $callback = null,
        #[SensitiveParameter]
        array|CreditCard|null $creditCard = null,
        #[SensitiveParameter]
        array|CreditCardHolderInfo|null $creditCardHolderInfo = null,
        public ?string $remoteIp = null,
        public ?bool $authorizeOnly = null,
        #[SensitiveParameter]
        public ?string $creditCardToken = null,
    ) {
        $this->split = $split !== null ? array_map(
            fn (array|Split $item): Split => $item instanceof Split ? $item : Split::fromArray($item),
            $split,
        ) : null;
        $this->callback = is_array($callback) ? Callback::fromArray($callback) : $callback;
        $this->creditCard = is_array($creditCard) ? CreditCard::fromArray($creditCard) : $creditCard;
        $this->creditCardHolderInfo = is_array($creditCardHolderInfo) ? CreditCardHolderInfo::fromArray($creditCardHolderInfo) : $creditCardHolderInfo;
        /** @var ?Discount */
        $coercedDiscount = Discount::coerce($discount);
        /** @var ?Interest */
        $coercedInterest = Interest::coerce($interest);
        /** @var ?Fine */
        $coercedFine = Fine::coerce($fine);
        $this->discount = $coercedDiscount;
        $this->interest = $coercedInterest;
        $this->fine = $coercedFine;
    }

    /** @return array{customer: string, billingType: BillingType|string, value: float, dueDate: string, description: ?string, externalReference: ?string, discount: ?Discount, interest: ?Interest, fine: ?Fine, postalService: ?bool, daysAfterDueDateToRegistrationCancellation: ?int, installmentCount: ?int, installmentValue: ?float, totalValue: ?float, pixAutomaticAuthorizationId: ?string, split: ?list<Split>, callback: ?Callback, creditCard: ?array<string, mixed>, creditCardHolderInfo: ?array<string, mixed>, remoteIp: ?string, authorizeOnly: ?bool, creditCardToken: ?string} */
    public function __debugInfo(): array
    {
        return [
            'customer' => $this->customer,
            'billingType' => $this->billingType,
            'value' => $this->value,
            'dueDate' => $this->dueDate,
            'description' => $this->description,
            'externalReference' => $this->externalReference,
            'discount' => $this->discount,
            'interest' => $this->interest,
            'fine' => $this->fine,
            'postalService' => $this->postalService,
            'daysAfterDueDateToRegistrationCancellation' => $this->daysAfterDueDateToRegistrationCancellation,
            'installmentCount' => $this->installmentCount,
            'installmentValue' => $this->installmentValue,
            'totalValue' => $this->totalValue,
            'pixAutomaticAuthorizationId' => $this->pixAutomaticAuthorizationId,
            'split' => $this->split,
            'callback' => $this->callback,
            'creditCard' => $this->creditCard?->__debugInfo(),
            'creditCardHolderInfo' => $this->creditCardHolderInfo?->__debugInfo(),
            'remoteIp' => $this->remoteIp,
            'authorizeOnly' => $this->authorizeOnly,
            'creditCardToken' => $this->creditCardToken !== null ? '***' : null,
        ];
    }

    /** @param array{customer?: string, billingType?: BillingType|string, value?: float, dueDate?: string, description?: string, externalReference?: string, discount?: array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float, interest?: array{value?: float}|Interest|float, fine?: array{value?: float, type?: FineType|string}|Fine|float, postalService?: bool, daysAfterDueDateToRegistrationCancellation?: int, installmentCount?: int, installmentValue?: float, totalValue?: float, pixAutomaticAuthorizationId?: string, split?: list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}>, callback?: array{successUrl?: string, autoRedirect?: bool}, creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}, remoteIp?: string, authorizeOnly?: bool, creditCardToken?: string} $data */
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
            daysAfterDueDateToRegistrationCancellation: $data['daysAfterDueDateToRegistrationCancellation'] ?? null,
            installmentCount: $data['installmentCount'] ?? null,
            installmentValue: $data['installmentValue'] ?? null,
            totalValue: $data['totalValue'] ?? null,
            pixAutomaticAuthorizationId: $data['pixAutomaticAuthorizationId'] ?? null,
            split: $data['split'] ?? null,
            callback: $data['callback'] ?? null,
            creditCard: $data['creditCard'] ?? null,
            creditCardHolderInfo: $data['creditCardHolderInfo'] ?? null,
            remoteIp: $data['remoteIp'] ?? null,
            authorizeOnly: $data['authorizeOnly'] ?? null,
            creditCardToken: $data['creditCardToken'] ?? null,
        );
    }
}
