<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Illuminate\Support\ServiceProvider;
use OwnerPro\Asaas\Support\AsaasConnector;
use RuntimeException;

final class AsaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/asaas.php', 'asaas');

        $this->app->singleton(AsaasClient::class, function (): AsaasClient {
            /** @var array{api_key: ?string, environment: string, timeout: int, connect_timeout: int} $config */
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
                )
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/asaas.php' => config_path('asaas.php'),
        ], 'asaas-config');
    }
}
