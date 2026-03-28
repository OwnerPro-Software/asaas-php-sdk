<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;

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

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['creditCard', 'creditCardHolderInfo'];
    }
}
