<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin AsaasClient
 */
final class Asaas extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AsaasClient::class;
    }
}
