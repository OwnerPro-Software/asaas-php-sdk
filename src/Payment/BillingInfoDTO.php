<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class BillingInfoDTO extends BaseDTO
{
    public ?string $bankSlipUrl = null;

    public ?string $invoiceUrl = null;
}
