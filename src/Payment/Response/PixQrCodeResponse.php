<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class PixQrCodeResponse extends BaseResponse
{
    public ?string $encodedImage = null;

    public ?string $payload = null;

    public ?string $expirationDate = null;
}
