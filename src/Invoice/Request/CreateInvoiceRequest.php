<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateInvoiceRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|Taxes $taxes */
    public function __construct(
        public readonly string $serviceDescription,
        public readonly string $observations,
        public readonly float $value,
        public readonly float $deductions,
        public readonly string $effectiveDate,
        public readonly string $municipalServiceName,
        public readonly array|Taxes $taxes,
        public readonly ?string $payment = null,
        public readonly ?string $installment = null,
        public readonly ?string $customer = null,
        public readonly ?string $externalReference = null,
        public readonly ?string $municipalServiceId = null,
        public readonly ?string $municipalServiceCode = null,
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
        return ['serviceDescription', 'observations', 'value', 'deductions', 'effectiveDate', 'municipalServiceName', 'taxes'];
    }
}
