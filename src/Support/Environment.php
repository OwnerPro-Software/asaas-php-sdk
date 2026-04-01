<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://api-sandbox.asaas.com',
            self::Production => 'https://api.asaas.com',
        };
    }
}
