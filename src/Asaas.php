<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Illuminate\Support\Facades\Facade;
use OwnerPro\Asaas\Contracts\AsaasClientContract;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use SensitiveParameter;

/** @mixin AsaasClientContract */
final class Asaas extends Facade
{
    public static function for(
        #[SensitiveParameter] string $apiKey,
        Environment|string|null $environment = null,
        ?int $timeout = null,
        ?int $connectTimeout = null,
    ): AsaasClient {
        /** @var array{environment: string, timeout: int, connect_timeout: int} $config */
        $config = config('asaas');

        return new AsaasClient(
            AsaasConnector::forLaravel(
                apiKey: $apiKey,
                environment: $environment ?? $config['environment'],
                timeout: $timeout ?? $config['timeout'],
                connectTimeout: $connectTimeout ?? $config['connect_timeout'],
            )
        );
    }

    /**
     * Resolve through the contract, not the concrete class, so the documented
     * test seam works: overriding the `AsaasClientContract` binding detaches
     * the container alias, which would otherwise leave the facade pointing at
     * the real client while injected code sees the fake.
     */
    protected static function getFacadeAccessor(): string
    {
        return AsaasClientContract::class;
    }
}
