<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreateInvoiceRequest
{
    use HasArrayFactory;

    public Taxes $taxes;

    /**
     * @param  array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}|Taxes  $taxes
     * @param  string  $effectiveDate  Format `YYYY-MM-DD`.
     */
    public function __construct(
        public string $serviceDescription,
        public string $observations,
        public float $value,
        public float $deductions,
        public string $effectiveDate,
        public string $municipalServiceName,
        array|Taxes $taxes,
        public ?string $payment = null,
        public ?string $installment = null,
        public ?string $customer = null,
        public ?string $externalReference = null,
        public ?string $municipalServiceId = null,
        public ?string $municipalServiceCode = null,
        public ?bool $updatePayment = null,
    ) {
        $this->taxes = is_array($taxes) ? Taxes::fromArray($taxes) : $taxes;
    }

    /** @param array{serviceDescription?: string, observations?: string, value?: float, deductions?: float, effectiveDate?: string, municipalServiceName?: string, taxes?: array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string}|Taxes, payment?: string, installment?: string, customer?: string, externalReference?: string, municipalServiceId?: string, municipalServiceCode?: string, updatePayment?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            serviceDescription: $data['serviceDescription'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: serviceDescription is required'),
            observations: $data['observations'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: observations is required'),
            value: $data['value'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: value is required'),
            deductions: $data['deductions'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: deductions is required'),
            effectiveDate: $data['effectiveDate'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: effectiveDate is required'),
            municipalServiceName: $data['municipalServiceName'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: municipalServiceName is required'),
            taxes: $data['taxes'] ?? throw new InvalidArgumentException('CreateInvoiceRequest: taxes is required'),
            payment: $data['payment'] ?? null,
            installment: $data['installment'] ?? null,
            customer: $data['customer'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            municipalServiceId: $data['municipalServiceId'] ?? null,
            municipalServiceCode: $data['municipalServiceCode'] ?? null,
            updatePayment: $data['updatePayment'] ?? null,
        );
    }
}
