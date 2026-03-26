<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class TokenizeCreditCardData
{
    use HasArrayFactory;

    /**
     * @param  array<string, mixed>  $creditCard
     * @param  array<string, mixed>  $creditCardHolderInfo
     */
    public function __construct(
        public readonly string $customer,
        public readonly array $creditCard,
        public readonly array $creditCardHolderInfo,
        public readonly string $remoteIp
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['customer', 'creditCard', 'creditCardHolderInfo', 'remoteIp'];
    }
}
