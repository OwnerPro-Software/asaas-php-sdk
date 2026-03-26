<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use OwnerPro\Asaas\Support\BaseDTO;

final class StaticQrCodeDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $encodedImage = null;

    public ?string $payload = null;
}
