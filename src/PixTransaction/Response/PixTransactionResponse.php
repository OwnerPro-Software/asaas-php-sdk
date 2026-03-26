<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PixTransactionResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $endToEndIdentifier = null;

    public ?string $finality = null;

    public ?float $value = null;

    public ?float $changeValue = null;

    public ?float $refundedValue = null;

    public ?string $effectiveDate = null;

    public ?string $scheduledDate = null;

    public ?string $status = null;

    public ?string $type = null;

    public ?string $originType = null;

    public ?string $conciliationIdentifier = null;

    public ?string $description = null;

    public ?string $transactionReceiptUrl = null;

    public ?string $refusalReason = null;

    public ?bool $canBeCanceled = null;

    public ?bool $canBeRefunded = null;

    public ?string $dateCreated = null;

    public ?string $externalReference = null;
}
