<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class UpdateInvoiceRequest
{
    use HasUpdatableArrayFactory;

    public Taxes|Missing $taxes;

    /**
     * @param  array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}|Taxes|Missing  $taxes
     * @param  string|Missing  $effectiveDate  Format `YYYY-MM-DD`.
     */
    public function __construct(
        public string|Missing $serviceDescription = Missing::Value,
        public string|Missing $observations = Missing::Value,
        public float|Missing $value = Missing::Value,
        public float|Missing $deductions = Missing::Value,
        public string|Missing $effectiveDate = Missing::Value,
        public string|Missing $municipalServiceName = Missing::Value,
        array|Taxes|Missing $taxes = Missing::Value,
        public string|Missing $externalReference = Missing::Value,
        public bool|Missing $updatePayment = Missing::Value,
    ) {
        $this->taxes = is_array($taxes) ? Taxes::fromArray($taxes) : $taxes;
    }

    /** @param array{serviceDescription?: string, observations?: string, value?: float, deductions?: float, effectiveDate?: string, municipalServiceName?: string, taxes?: array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}|Taxes, externalReference?: string, updatePayment?: bool} $data */
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
            updatePayment: array_key_exists('updatePayment', $data) ? $data['updatePayment'] : Missing::Value,
        );
    }
}
