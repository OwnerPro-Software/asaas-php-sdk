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
        array|Taxes|Missing $taxes = Missing::Value,
        public string|Missing $externalReference = Missing::Value,
        public bool|Missing $updatePayment = Missing::Value,
    ) {
        $this->taxes = is_array($taxes) ? Taxes::fromArray($taxes) : $taxes;
    }

    /** @param array{serviceDescription?: string, observations?: string, value?: float, deductions?: float, effectiveDate?: string, taxes?: array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}|Taxes, externalReference?: string, updatePayment?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            serviceDescription: $data['serviceDescription'] ?? Missing::Value,
            observations: $data['observations'] ?? Missing::Value,
            value: $data['value'] ?? Missing::Value,
            deductions: $data['deductions'] ?? Missing::Value,
            effectiveDate: $data['effectiveDate'] ?? Missing::Value,
            taxes: $data['taxes'] ?? Missing::Value,
            externalReference: $data['externalReference'] ?? Missing::Value,
            updatePayment: $data['updatePayment'] ?? Missing::Value,
        );
    }
}
