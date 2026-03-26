<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class UpdateInvoiceData
{
    use HasArrayFactory;

    /** @param array<string, mixed>|null $taxes */
    public function __construct(
        public readonly ?string $serviceDescription = null,
        public readonly ?string $observations = null,
        public readonly ?float $value = null,
        public readonly ?float $deductions = null,
        public readonly ?string $effectiveDate = null,
        public readonly ?string $municipalServiceName = null,
        public readonly ?array $taxes = null,
        public readonly ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
