<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;

final readonly class PayWithCreditCardRequest
{
    use HasArrayFactory;

    public CreditCard $creditCard;

    public CreditCardHolderInfo $creditCardHolderInfo;

    /**
     * @param  array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}|CreditCard  $creditCard
     * @param  array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}|CreditCardHolderInfo  $creditCardHolderInfo
     */
    public function __construct(
        #[SensitiveParameter]
        array|CreditCard $creditCard,
        #[SensitiveParameter]
        array|CreditCardHolderInfo $creditCardHolderInfo,
    ) {
        $this->creditCard = is_array($creditCard) ? CreditCard::fromArray($creditCard) : $creditCard;
        $this->creditCardHolderInfo = is_array($creditCardHolderInfo) ? CreditCardHolderInfo::fromArray($creditCardHolderInfo) : $creditCardHolderInfo;
    }

    /** @param array{creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            creditCard: CreditCard::fromArray($data['creditCard'] ?? throw new InvalidArgumentException('PayWithCreditCardRequest: creditCard is required')),
            creditCardHolderInfo: CreditCardHolderInfo::fromArray($data['creditCardHolderInfo'] ?? throw new InvalidArgumentException('PayWithCreditCardRequest: creditCardHolderInfo is required')),
        );
    }
}
