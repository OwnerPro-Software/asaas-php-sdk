<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class UpdateInvoiceRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|Taxes|null $taxes */
    public function __construct(
        public readonly ?string $serviceDescription = null,
        public readonly ?string $observations = null,
        public readonly ?float $value = null,
        public readonly ?float $deductions = null,
        public readonly ?string $effectiveDate = null,
        public readonly ?string $municipalServiceName = null,
        public readonly array|Taxes|null $taxes = null,
        public readonly ?string $externalReference = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if ($this->taxes instanceof Taxes) {
            $data['taxes'] = $this->taxes->toArray();
        }

        return $data;
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
