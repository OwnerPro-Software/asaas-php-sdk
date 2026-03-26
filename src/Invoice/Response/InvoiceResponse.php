<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class InvoiceResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $status = null;

    public ?string $customer = null;

    public ?string $payment = null;

    public ?string $installment = null;

    public ?string $type = null;

    public ?string $statusDescription = null;

    public ?string $serviceDescription = null;

    public ?string $pdfUrl = null;

    public ?string $xmlUrl = null;

    public ?string $rpsSerie = null;

    public ?string $rpsNumber = null;

    public ?string $number = null;

    public ?string $validationCode = null;

    public ?float $value = null;

    public ?float $deductions = null;

    public ?string $effectiveDate = null;

    public ?string $observations = null;

    public ?string $externalReference = null;

    /** @var array<string, mixed>|null */
    public ?array $taxes = null;

    public ?string $municipalServiceId = null;

    public ?string $municipalServiceCode = null;

    public ?string $municipalServiceName = null;
}
