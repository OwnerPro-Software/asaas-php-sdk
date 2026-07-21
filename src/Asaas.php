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
        ?int $connectTimeout = null,
        ?bool $throwOnTransportFailure = null,
    ): AsaasClient {
        /** @var array{environment: string, timeout: int, connect_timeout: int, throw_on_transport_failure: bool} $config */
        $config = config('asaas');

        return new AsaasClient(
            AsaasConnector::forLaravel(
                apiKey: $apiKey,
                environment: $environment ?? $config['environment'],
                timeout: $timeout ?? $config['timeout'],
                connectTimeout: $connectTimeout ?? $config['connect_timeout'],
                throwOnTransportFailure: $throwOnTransportFailure ?? $config['throw_on_transport_failure'],
            )
        );
    }

    protected static function getFacadeAccessor(): string
    {
        return AsaasClient::class;
    }
}
