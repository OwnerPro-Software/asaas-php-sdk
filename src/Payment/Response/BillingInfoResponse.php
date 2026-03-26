<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class BillingInfoResponse extends BaseResponse
{
    public ?string $bankSlipUrl = null;

    public ?string $invoiceUrl = null;
}
