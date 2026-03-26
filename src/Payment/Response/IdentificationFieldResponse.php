<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class IdentificationFieldResponse extends BaseResponse
{
    public ?string $identificationField = null;

    public ?string $nossoNumero = null;

    public ?string $barCode = null;
}
