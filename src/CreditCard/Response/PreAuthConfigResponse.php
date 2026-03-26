<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PreAuthConfigResponse extends BaseResponse
{
    public ?int $daysToExpire = null;
}
