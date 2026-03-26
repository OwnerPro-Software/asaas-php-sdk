<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

use OwnerPro\Asaas\Support\BaseDTO;

final class BillPaymentDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $status = null;

    public ?float $value = null;

    public ?float $discount = null;

    public ?float $interest = null;

    public ?float $fine = null;

    public ?string $identificationField = null;

    public ?string $dueDate = null;

    public ?string $scheduleDate = null;

    public ?string $paymentDate = null;

    public ?float $fee = null;

    public ?string $description = null;

    public ?string $companyName = null;

    public ?string $transactionReceiptUrl = null;

    public ?bool $canBeCancelled = null;

    public ?string $externalReference = null;

    /** @var list<string>|null */
    public ?array $failReasons = null;
}
