<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Statement;

use OwnerPro\Asaas\Support\BaseDTO;

final class StatementDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $type = null;

    public ?float $value = null;

    public ?float $balance = null;

    public ?string $date = null;

    public ?string $description = null;
}
