<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Statement\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class StatementResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $type = null;

    public ?float $value = null;

    public ?float $balance = null;

    public ?string $date = null;

    public ?string $description = null;
}
