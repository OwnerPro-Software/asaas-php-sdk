<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

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

    /** @param array{serviceDescription?: string, observations?: string, value?: float, deductions?: float, effectiveDate?: string, municipalServiceName?: string, taxes?: array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}, payment?: string, installment?: string, customer?: string, externalReference?: string, municipalServiceId?: string, municipalServiceCode?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            serviceDescription: $data['serviceDescription'] ?? throw new TypeError('serviceDescription is required'),
            observations: $data['observations'] ?? throw new TypeError('observations is required'),
            value: $data['value'] ?? throw new TypeError('value is required'),
            deductions: $data['deductions'] ?? throw new TypeError('deductions is required'),
            effectiveDate: $data['effectiveDate'] ?? throw new TypeError('effectiveDate is required'),
            municipalServiceName: $data['municipalServiceName'] ?? throw new TypeError('municipalServiceName is required'),
            taxes: Taxes::fromArray($data['taxes'] ?? throw new TypeError('taxes is required')),
            payment: $data['payment'] ?? null,
            installment: $data['installment'] ?? null,
            customer: $data['customer'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            municipalServiceId: $data['municipalServiceId'] ?? null,
            municipalServiceCode: $data['municipalServiceCode'] ?? null,
        );
    }
}
