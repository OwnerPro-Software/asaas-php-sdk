<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class TokenBucketResponse extends BaseResponse
{
    public ?int $capacity = null;

    public ?int $remaining = null;
}
