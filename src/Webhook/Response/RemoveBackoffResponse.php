<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class RemoveBackoffResponse extends BaseResponse
{
    public ?bool $success = null;
}
