<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class BillSimulationResponse extends BaseResponse
{
    public ?string $minimumScheduleDate = null;

    public ?float $fee = null;

    /** @var array<string, mixed>|null */
    public ?array $bankSlipInfo = null;
}
