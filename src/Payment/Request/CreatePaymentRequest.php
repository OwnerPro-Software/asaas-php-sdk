<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreatePaymentRequest
{
    use HasArrayFactory;

    /**
     * @param  list<array<string, mixed>|Split>|null  $split
     * @param  array<string, mixed>|Callback|null  $callback
     * @param  array<string, mixed>|CreditCard|null  $creditCard
     * @param  array<string, mixed>|CreditCardHolderInfo|null  $creditCardHolderInfo
     */
    public function __construct(
        public readonly string $customer,
        public readonly string $billingType,
        public readonly float $value,
        public readonly string $dueDate,
        public readonly ?string $description = null,
        public readonly ?string $externalReference = null,
        public readonly ?float $discount = null,
        public readonly ?float $interest = null,
        public readonly ?float $fine = null,
        public readonly ?bool $postalService = null,
        public readonly ?array $split = null,
        public readonly array|Callback|null $callback = null,
        public readonly array|CreditCard|null $creditCard = null,
        public readonly array|CreditCardHolderInfo|null $creditCardHolderInfo = null,
        public readonly ?string $remoteIp = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if (is_array($this->split)) {
            $data['split'] = array_map(
                fn (array|Split $item): array => $item instanceof Split ? $item->toArray() : $item,
                $this->split,
            );
        }

        if ($this->callback instanceof Callback) {
            $data['callback'] = $this->callback->toArray();
        }

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
        return ['customer', 'billingType', 'value', 'dueDate'];
    }
}
