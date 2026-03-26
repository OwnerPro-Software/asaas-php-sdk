<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final class DeletedDTO extends BaseDTO
{
    public ?bool $deleted = null;

    public ?string $id = null;
}
