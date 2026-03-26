<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final class DeletedResponse extends BaseResponse
{
    public ?bool $deleted = null;

    public ?string $id = null;
}
