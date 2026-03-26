<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class ViewingInfoDTO extends BaseDTO
{
    public ?bool $viewed = null;

    public ?string $viewedDate = null;
}
