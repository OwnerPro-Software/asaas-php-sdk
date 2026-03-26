<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use OwnerPro\Asaas\Support\BaseDTO;

final class PixDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $key = null;

    public ?string $type = null;

    public ?string $status = null;

    public ?string $dateCreated = null;

    public ?bool $canBeDeleted = null;

    public ?string $cannotBeDeletedReason = null;

    /** @var array<string, mixed>|null */
    public ?array $qrCode = null;
}
