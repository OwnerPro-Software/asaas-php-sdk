<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Illuminate\Support\Facades\Facade;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use SensitiveParameter;

/** @mixin AsaasClient */
final class Asaas extends Facade
{
    public static function for(
        #[SensitiveParameter] string $apiKey,
        Environment|string|null $environment = null,
        ?int $timeout = null,
    ): AsaasClient {
        /** @var array{environment: string, timeout: int} $config */
        $config = config('asaas');

        return new AsaasClient(
            AsaasConnector::forLaravel(
                apiKey: $apiKey,
                environment: $environment ?? $config['environment'],
                timeout: $timeout ?? $config['timeout'],
            )
        );
    }

    protected static function getFacadeAccessor(): string
    {
        return AsaasClient::class;
    }
}
