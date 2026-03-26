<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class PaymentStatusDTO extends BaseDTO
{
    public ?string $status = null;
}
