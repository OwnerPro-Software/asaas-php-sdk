<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class SimulateBillPaymentRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $identificationField = null,
        public ?string $barCode = null,
    ) {}

    /** @param array{identificationField?: string, barCode?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            identificationField: $data['identificationField'] ?? null,
            barCode: $data['barCode'] ?? null,
        );
    }
}
