<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class PixQrCodeDTO extends BaseDTO
{
    public ?string $encodedImage = null;

    public ?string $payload = null;

    public ?string $expirationDate = null;
}
