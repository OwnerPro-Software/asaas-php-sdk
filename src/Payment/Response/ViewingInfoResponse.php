<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class ViewingInfoResponse extends BaseResponse
{
    public ?bool $viewed = null;

    public ?string $viewedDate = null;
}
