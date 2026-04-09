<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class UpdatePaymentRequest
{
    use HasUpdatableArrayFactory;

    /** @param list<Split>|Missing|null $split */
    public function __construct(
        public BillingType|string|Missing|null $billingType = Missing::Value,
        public float|Missing|null $value = Missing::Value,
        public string|Missing|null $dueDate = Missing::Value,
        public string|Missing|null $description = Missing::Value,
        public string|Missing|null $externalReference = Missing::Value,
        public float|Missing|null $discount = Missing::Value,
        public float|Missing|null $interest = Missing::Value,
        public float|Missing|null $fine = Missing::Value,
        public bool|Missing|null $postalService = Missing::Value,
        public array|Missing|null $split = Missing::Value,
    ) {}

    /** @param array{billingType?: BillingType|string|null, value?: float|null, dueDate?: string|null, description?: string|null, externalReference?: string|null, discount?: float|null, interest?: float|null, fine?: float|null, postalService?: bool|null, split?: list<array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string}>|null} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            billingType: array_key_exists('billingType', $data) ? $data['billingType'] : Missing::Value,
            value: array_key_exists('value', $data) ? $data['value'] : Missing::Value,
            dueDate: array_key_exists('dueDate', $data) ? $data['dueDate'] : Missing::Value,
            description: array_key_exists('description', $data) ? $data['description'] : Missing::Value,
            externalReference: array_key_exists('externalReference', $data) ? $data['externalReference'] : Missing::Value,
            discount: array_key_exists('discount', $data) ? $data['discount'] : Missing::Value,
            interest: array_key_exists('interest', $data) ? $data['interest'] : Missing::Value,
            fine: array_key_exists('fine', $data) ? $data['fine'] : Missing::Value,
            postalService: array_key_exists('postalService', $data) ? $data['postalService'] : Missing::Value,
            split: array_key_exists('split', $data)
                ? (is_array($data['split']) ? array_map(Split::fromArray(...), $data['split']) : $data['split'])
                : Missing::Value,
        );
    }
}
