<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

use OwnerPro\Asaas\Support\BaseDTO;

final class TransferDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $type = null;

    public ?string $dateCreated = null;

    public ?float $value = null;

    public ?float $netValue = null;

    public ?string $status = null;

    public ?float $transferFee = null;

    public ?string $effectiveDate = null;

    public ?string $scheduleDate = null;

    public ?bool $authorized = null;

    public ?string $failReason = null;

    public ?string $externalReference = null;

    public ?string $transactionReceiptUrl = null;

    public ?string $operationType = null;

    public ?string $description = null;

    public ?bool $recurring = null;
}
