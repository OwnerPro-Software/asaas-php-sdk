<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateInvoiceRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed> $taxes */
    public function __construct(
        public readonly string $serviceDescription,
        public readonly string $observations,
        public readonly float $value,
        public readonly float $deductions,
        public readonly string $effectiveDate,
        public readonly string $municipalServiceName,
        public readonly array $taxes,
        public readonly ?string $payment = null,
        public readonly ?string $installment = null,
        public readonly ?string $customer = null,
        public readonly ?string $externalReference = null,
        public readonly ?string $municipalServiceId = null,
        public readonly ?string $municipalServiceCode = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['serviceDescription', 'observations', 'value', 'deductions', 'effectiveDate', 'municipalServiceName', 'taxes'];
    }
}
