<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Illuminate\Support\ServiceProvider;
use Override;
use OwnerPro\Asaas\Contracts\AsaasClientContract;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\DumpRedaction;
use RuntimeException;

final class AsaasServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/asaas.php', 'asaas');

        $this->app->singleton(AsaasClient::class, function (): AsaasClient {
            /** @var array{api_key: ?string, environment: string, timeout: int, connect_timeout: int, throw_on_transport_failure: bool} $config */
            $config = config('asaas');

            if (! is_string($config['api_key']) || $config['api_key'] === '') {
                throw new RuntimeException(
                    'The asaas.api_key config value is required. Set the ASAAS_API_KEY environment variable.'
                );
            }

            return new AsaasClient(
                AsaasConnector::forLaravel(
                    apiKey: $config['api_key'],
                    environment: $config['environment'],
                    timeout: $config['timeout'],
                    connectTimeout: $config['connect_timeout'],
                    throwOnTransportFailure: $config['throw_on_transport_failure'],
                )
            );
        });

        $this->app->alias(AsaasClient::class, AsaasClientContract::class);
    }

    public function boot(): void
    {
        DumpRedaction::register();

        $this->publishes([
            __DIR__.'/../config/asaas.php' => config_path('asaas.php'),
        ], 'asaas-config');
    }
}
