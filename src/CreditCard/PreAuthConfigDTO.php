<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\Support\BaseDTO;

final class PreAuthConfigDTO extends BaseDTO
{
    public ?int $daysToExpire = null;
}
