<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Payment\PaymentDocumentType;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class UpdatePaymentDocumentRequest
{
    use HasArrayFactory;

    public function __construct(
        public bool $availableAfterPayment,
        public PaymentDocumentType|string $type,
    ) {}

    /** @param array{availableAfterPayment: bool, type: PaymentDocumentType|string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            availableAfterPayment: $data['availableAfterPayment'],
            type: $data['type'],
        );
    }
}
