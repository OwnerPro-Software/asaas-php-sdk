<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Support\BaseDTO;

final class IdentificationFieldDTO extends BaseDTO
{
    public ?string $identificationField = null;

    public ?string $nossoNumero = null;

    public ?string $barCode = null;
}
