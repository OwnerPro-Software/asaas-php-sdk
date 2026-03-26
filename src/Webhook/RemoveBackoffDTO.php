<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use OwnerPro\Asaas\Support\BaseDTO;

final class RemoveBackoffDTO extends BaseDTO
{
    public ?bool $success = null;
}
