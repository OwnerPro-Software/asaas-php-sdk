<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use OwnerPro\Asaas\Support\BaseDTO;

final class AccessTokenDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $name = null;

    public ?bool $enabled = null;

    public ?string $apiKey = null;

    public ?string $expirationDate = null;
}
