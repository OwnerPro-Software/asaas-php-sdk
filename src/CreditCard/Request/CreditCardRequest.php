<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreditCardRequest
{
    use HasArrayFactory;

    /**
     * @param  array<string, mixed>|CreditCard  $creditCard
     * @param  array<string, mixed>|CreditCardHolderInfo  $creditCardHolderInfo
     */
    public function __construct(
        public string $customer,
        public array|CreditCard $creditCard,
        public array|CreditCardHolderInfo $creditCardHolderInfo,
        public string $remoteIp,
    ) {}

    /** @param array{customer?: string, creditCard?: array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string}, creditCardHolderInfo?: array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string}, remoteIp?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            customer: $data['customer'] ?? throw new InvalidArgumentException('customer is required'),
            creditCard: CreditCard::fromArray($data['creditCard'] ?? throw new InvalidArgumentException('creditCard is required')),
            creditCardHolderInfo: CreditCardHolderInfo::fromArray($data['creditCardHolderInfo'] ?? throw new InvalidArgumentException('creditCardHolderInfo is required')),
            remoteIp: $data['remoteIp'] ?? throw new InvalidArgumentException('remoteIp is required'),
        );
    }
}
