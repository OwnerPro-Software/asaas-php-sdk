<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

use OwnerPro\Asaas\Support\BaseDTO;

final class BillSimulationDTO extends BaseDTO
{
    public ?string $minimumScheduleDate = null;

    public ?float $fee = null;

    /** @var array<string, mixed>|null */
    public ?array $bankSlipInfo = null;
}
