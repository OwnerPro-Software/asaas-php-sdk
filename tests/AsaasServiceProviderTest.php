<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use OwnerPro\Asaas\Asaas;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\AsaasServiceProvider;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;

mutates(AsaasServiceProvider::class, Asaas::class);

it('registers AsaasClient as singleton', function () {
    $client1 = app(AsaasClient::class);
    $client2 = app(AsaasClient::class);

    expect($client1)->toBeInstanceOf(AsaasClient::class);
    expect($client1)->toBe($client2);
});

it('resolves AsaasClient via facade', function () {
    $client = Asaas::getFacadeRoot();

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('makes config available after registration', function () {
    expect(config('asaas'))->toHaveKeys(['api_key', 'environment', 'timeout']);
});

it('restores config after re-registration', function () {
    $this->app['config']->set('asaas', []);

    (new AsaasServiceProvider($this->app))->register();

    expect(config('asaas'))->toHaveKeys(['api_key', 'environment', 'timeout']);
});

it('registers config source path for publishing', function () {
    $paths = ServiceProvider::pathsToPublish(AsaasServiceProvider::class, 'asaas-config');

    expect($paths)->not->toBeEmpty();

    $sourcePath = array_key_first($paths);
    expect($sourcePath)->toEndWith('config/asaas.php');
    expect(file_exists($sourcePath))->toBeTrue();
});

it('Asaas::for() returns AsaasClient with config defaults', function () {
    $client = Asaas::for(apiKey: 'tenant-key');

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('Asaas::for() overrides environment with enum', function () {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'test_123'], 200)]);

    $client = Asaas::for(apiKey: 'tenant-key', environment: Environment::Production);
    $client->payments()->find('test_123');

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.asaas.com/'));
});

it('Asaas::for() overrides environment with string', function () {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'test_123'], 200)]);

    $client = Asaas::for(apiKey: 'tenant-key', environment: 'production');
    $client->payments()->find('test_123');

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.asaas.com/'));
});

it('Asaas::for() throws ValueError for invalid environment string', function () {
    Asaas::for(apiKey: 'tenant-key', environment: 'staging');
})->throws(ValueError::class);

it('Asaas::for() overrides timeout', function () {
    Http::fake();

    $client = Asaas::for(apiKey: 'tenant-key', timeout: 60);
    $connector = (new ReflectionProperty(AsaasClient::class, 'connector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['timeout'])->toBe(60);
});

it('Asaas::for() uses default connect timeout from config', function () {
    Http::fake();

    $client = Asaas::for(apiKey: 'tenant-key');
    $connector = (new ReflectionProperty(AsaasClient::class, 'connector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['connect_timeout'])->toBe(10);
});

it('Asaas::for() overrides connect timeout', function () {
    Http::fake();

    $client = Asaas::for(apiKey: 'tenant-key', connectTimeout: 5);
    $connector = (new ReflectionProperty(AsaasClient::class, 'connector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['connect_timeout'])->toBe(5);
});

it('throws ValueError when environment config is invalid', function () {
    $this->app['config']->set('asaas.environment', 'staging');
    $this->app->forgetInstance(AsaasClient::class);

    app(AsaasClient::class);
})->throws(ValueError::class);

it('throws RuntimeException when api_key is not configured', function () {
    $this->app['config']->set('asaas.api_key', null);
    $this->app->forgetInstance(AsaasClient::class);

    app(AsaasClient::class);
})->throws(RuntimeException::class, 'The asaas.api_key config value is required.');

it('throws RuntimeException when api_key is empty string', function () {
    $this->app['config']->set('asaas.api_key', '');
    $this->app->forgetInstance(AsaasClient::class);

    app(AsaasClient::class);
})->throws(RuntimeException::class, 'The asaas.api_key config value is required.');
