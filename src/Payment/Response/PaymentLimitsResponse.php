<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PaymentLimitsResponse extends BaseResponse
{
    /** @var array<string, mixed>|null */
    public ?array $creation = null;

    /** @var array<string, mixed>|null */
    public ?array $transfer = null;
}
