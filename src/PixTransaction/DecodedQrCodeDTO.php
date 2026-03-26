<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

use OwnerPro\Asaas\Support\BaseDTO;

final class DecodedQrCodeDTO extends BaseDTO
{
    public ?string $payload = null;

    public ?string $type = null;

    public ?float $value = null;
}
