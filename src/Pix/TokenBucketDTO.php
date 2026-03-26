<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use OwnerPro\Asaas\Support\BaseDTO;

final class TokenBucketDTO extends BaseDTO
{
    public ?int $capacity = null;

    public ?int $remaining = null;
}
