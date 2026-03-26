<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class PaymentLimitsDTO extends BaseDTO
{
    /** @var array<string, mixed>|null */
    public ?array $creation = null;

    /** @var array<string, mixed>|null */
    public ?array $transfer = null;
}
