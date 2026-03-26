<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PaymentStatusResponse extends BaseResponse
{
    public ?string $status = null;
}
