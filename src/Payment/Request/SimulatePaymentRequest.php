<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class SimulatePaymentRequest
{
    use HasArrayFactory;

    /** @param list<BillingType|string> $billingTypes */
    public function __construct(
        public float $value,
        public array $billingTypes,
        public ?int $installmentCount = null,
    ) {}

    /** @param array{value?: float, billingTypes?: list<BillingType|string>, installmentCount?: int} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('SimulatePaymentRequest: value is required'),
            billingTypes: $data['billingTypes'] ?? throw new InvalidArgumentException('SimulatePaymentRequest: billingTypes is required'),
            installmentCount: $data['installmentCount'] ?? null,
        );
    }
}
