<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class ReceivePaymentInCashRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $paymentDate = null,
        public ?float $value = null,
        public ?bool $notifyCustomer = null,
    ) {}

    /** @param array{paymentDate?: string, value?: float, notifyCustomer?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            paymentDate: $data['paymentDate'] ?? null,
            value: $data['value'] ?? null,
            notifyCustomer: $data['notifyCustomer'] ?? null,
        );
    }
}
