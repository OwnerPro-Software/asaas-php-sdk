<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class CreditCardRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public CreditCard $creditCard;

    public CreditCardHolderInfo $creditCardHolderInfo;

    /**
     * @param  array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}|CreditCard  $creditCard
     * @param  array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}|CreditCardHolderInfo  $creditCardHolderInfo
     */
    public function __construct(
        public string $customer,
        #[SensitiveParameter]
        array|CreditCard $creditCard,
        #[SensitiveParameter]
        array|CreditCardHolderInfo $creditCardHolderInfo,
        public string $remoteIp,
    ) {
        $this->creditCard = is_array($creditCard) ? CreditCard::fromArray($creditCard) : $creditCard;
        $this->creditCardHolderInfo = is_array($creditCardHolderInfo) ? CreditCardHolderInfo::fromArray($creditCardHolderInfo) : $creditCardHolderInfo;
    }

    /** @return array{customer: string, creditCard: array<string, mixed>, creditCardHolderInfo: array<string, mixed>, remoteIp: string} */
    public function __debugInfo(): array
    {
        return [
            'customer' => $this->customer,
            'creditCard' => $this->creditCard->__debugInfo(),
            'creditCardHolderInfo' => $this->creditCardHolderInfo->__debugInfo(),
            'remoteIp' => $this->remoteIp,
        ];
    }

    /** @param array{customer?: string, creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}, remoteIp?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            customer: $data['customer'] ?? throw new InvalidArgumentException('CreditCardRequest: customer is required'),
            creditCard: CreditCard::fromArray($data['creditCard'] ?? throw new InvalidArgumentException('CreditCardRequest: creditCard is required')),
            creditCardHolderInfo: CreditCardHolderInfo::fromArray($data['creditCardHolderInfo'] ?? throw new InvalidArgumentException('CreditCardRequest: creditCardHolderInfo is required')),
            remoteIp: $data['remoteIp'] ?? throw new InvalidArgumentException('CreditCardRequest: remoteIp is required'),
        );
    }
}
