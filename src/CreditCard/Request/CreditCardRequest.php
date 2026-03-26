<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard\Request;

use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreditCardRequest
{
    use HasArrayFactory;

    /**
     * @param  array<string, mixed>|CreditCard  $creditCard
     * @param  array<string, mixed>|CreditCardHolderInfo  $creditCardHolderInfo
     */
    public function __construct(
        public readonly string $customer,
        public readonly array|CreditCard $creditCard,
        public readonly array|CreditCardHolderInfo $creditCardHolderInfo,
        public readonly string $remoteIp,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = get_object_vars($this);

        if ($this->creditCard instanceof CreditCard) {
            $data['creditCard'] = $this->creditCard->toArray();
        }

        if ($this->creditCardHolderInfo instanceof CreditCardHolderInfo) {
            $data['creditCardHolderInfo'] = $this->creditCardHolderInfo->toArray();
        }

        return $data;
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['customer', 'creditCard', 'creditCardHolderInfo', 'remoteIp'];
    }
}
