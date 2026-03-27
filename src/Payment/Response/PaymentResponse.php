<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PaymentResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $dateCreated = null;

    public ?string $customer = null;

    public ?string $subscription = null;

    public ?string $installment = null;

    public ?string $paymentLink = null;

    public ?float $value = null;

    public ?float $netValue = null;

    public ?string $billingType = null;

    public ?string $status = null;

    public ?string $dueDate = null;

    public ?string $originalDueDate = null;

    public ?string $paymentDate = null;

    public ?string $clientPaymentDate = null;

    public ?string $description = null;

    public ?string $externalReference = null;

    public ?string $invoiceUrl = null;

    public ?string $bankSlipUrl = null;

    public ?string $transactionReceiptUrl = null;

    public ?string $invoiceNumber = null;

    public ?bool $deleted = null;

    public ?bool $anticipated = null;

    public ?bool $anticipable = null;

    public ?string $creditDate = null;

    public ?string $estimatedCreditDate = null;

    public ?string $nossoNumero = null;

    public ?bool $postalService = null;
}
