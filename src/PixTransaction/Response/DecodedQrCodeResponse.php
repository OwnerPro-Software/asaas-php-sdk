<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class DecodedQrCodeResponse extends BaseResponse
{
    public ?string $payload = null;

    public ?string $type = null;

    public ?float $value = null;
}
