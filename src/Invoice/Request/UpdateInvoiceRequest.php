<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class UpdateInvoiceRequest
{
    use HasUpdatableArrayFactory;

    /** @param array<string, mixed>|Taxes|Missing|null $taxes */
    public function __construct(
        public string|Missing|null $serviceDescription = Missing::Value,
        public string|Missing|null $observations = Missing::Value,
        public float|Missing|null $value = Missing::Value,
        public float|Missing|null $deductions = Missing::Value,
        public string|Missing|null $effectiveDate = Missing::Value,
        public string|Missing|null $municipalServiceName = Missing::Value,
        public array|Taxes|Missing|null $taxes = Missing::Value,
        public string|Missing|null $externalReference = Missing::Value,
    ) {}

    /** @param array{serviceDescription?: string|null, observations?: string|null, value?: float|null, deductions?: float|null, effectiveDate?: string|null, municipalServiceName?: string|null, taxes?: array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}|null, externalReference?: string|null} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            serviceDescription: array_key_exists('serviceDescription', $data) ? $data['serviceDescription'] : Missing::Value,
            observations: array_key_exists('observations', $data) ? $data['observations'] : Missing::Value,
            value: array_key_exists('value', $data) ? $data['value'] : Missing::Value,
            deductions: array_key_exists('deductions', $data) ? $data['deductions'] : Missing::Value,
            effectiveDate: array_key_exists('effectiveDate', $data) ? $data['effectiveDate'] : Missing::Value,
            municipalServiceName: array_key_exists('municipalServiceName', $data) ? $data['municipalServiceName'] : Missing::Value,
            taxes: array_key_exists('taxes', $data)
                ? (is_array($data['taxes']) ? Taxes::fromArray($data['taxes']) : $data['taxes'])
                : Missing::Value,
            externalReference: array_key_exists('externalReference', $data) ? $data['externalReference'] : Missing::Value,
        );
    }
}
