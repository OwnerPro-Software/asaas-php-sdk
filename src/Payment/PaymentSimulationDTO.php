<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class PaymentSimulationDTO extends BaseDTO
{
    public ?float $value = null;

    public ?float $netValue = null;
}
