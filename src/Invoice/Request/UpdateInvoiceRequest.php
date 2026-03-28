<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class UpdateInvoiceRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|Taxes|null $taxes */
    public function __construct(
        public ?string $serviceDescription = null,
        public ?string $observations = null,
        public ?float $value = null,
        public ?float $deductions = null,
        public ?string $effectiveDate = null,
        public ?string $municipalServiceName = null,
        public array|Taxes|null $taxes = null,
        public ?string $externalReference = null,
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
