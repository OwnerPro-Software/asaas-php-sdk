<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

final readonly class PayWithCreditCardRequest
{
    use HasArrayFactory;

    /**
     * @param  array<string, mixed>|CreditCard  $creditCard
     * @param  array<string, mixed>|CreditCardHolderInfo  $creditCardHolderInfo
     */
    public function __construct(
        public array|CreditCard $creditCard,
        public array|CreditCardHolderInfo $creditCardHolderInfo,
    ) {}

    /** @param array{creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            creditCard: CreditCard::fromArray($data['creditCard'] ?? throw new TypeError('creditCard is required')),
            creditCardHolderInfo: CreditCardHolderInfo::fromArray($data['creditCardHolderInfo'] ?? throw new TypeError('creditCardHolderInfo is required')),
        );
    }
}
