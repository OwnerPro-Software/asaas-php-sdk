<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Illuminate\Support\ServiceProvider;

final class AsaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/asaas.php', 'asaas');

        $this->app->singleton(AsaasClient::class, function (): AsaasClient {
            /** @var array{api_key: string, environment: string, timeout: int} $config */
            $config = config('asaas');

            return new AsaasClient(
                apiKey: $config['api_key'],
                environment: $config['environment'],
                timeout: $config['timeout'],
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
