<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PaymentSimulationResponse extends BaseResponse
{
    public ?float $value = null;

    public ?float $netValue = null;
}
