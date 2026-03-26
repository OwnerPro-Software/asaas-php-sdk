<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use OwnerPro\Asaas\AsaasServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AsaasServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('asaas.api_key', 'test-api-key');
        $app['config']->set('asaas.environment', 'sandbox');
        $app['config']->set('asaas.timeout', 30);
    }
}
