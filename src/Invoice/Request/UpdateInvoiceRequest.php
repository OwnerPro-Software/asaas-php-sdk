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

    /** @param array{serviceDescription?: string, observations?: string, value?: float, deductions?: float, effectiveDate?: string, municipalServiceName?: string, taxes?: array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}, externalReference?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            serviceDescription: $data['serviceDescription'] ?? null,
            observations: $data['observations'] ?? null,
            value: $data['value'] ?? null,
            deductions: $data['deductions'] ?? null,
            effectiveDate: $data['effectiveDate'] ?? null,
            municipalServiceName: $data['municipalServiceName'] ?? null,
            taxes: isset($data['taxes']) ? Taxes::fromArray($data['taxes']) : null,
            externalReference: $data['externalReference'] ?? null,
        );
    }
}
