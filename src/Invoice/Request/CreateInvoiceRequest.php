<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreateInvoiceRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|Taxes $taxes */
    public function __construct(
        public string $serviceDescription,
        public string $observations,
        public float $value,
        public float $deductions,
        public string $effectiveDate,
        public string $municipalServiceName,
        public array|Taxes $taxes,
        public ?string $payment = null,
        public ?string $installment = null,
        public ?string $customer = null,
        public ?string $externalReference = null,
        public ?string $municipalServiceId = null,
        public ?string $municipalServiceCode = null,
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
