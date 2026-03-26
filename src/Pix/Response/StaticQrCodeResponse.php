<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class StaticQrCodeResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $encodedImage = null;

    public ?string $payload = null;
}
