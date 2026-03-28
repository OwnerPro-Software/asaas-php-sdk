<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class AccessTokenResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $name = null;

    public ?bool $enabled = null;

    public ?string $apiKey = null;

    public ?string $expirationDate = null;

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        $attributes = $this->toArray();

        if (isset($attributes['apiKey']) && is_string($attributes['apiKey'])) {
            $attributes['apiKey'] = '***';
        }

        return $attributes;
    }
}
