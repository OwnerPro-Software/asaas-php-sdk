<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use OwnerPro\Asaas\Asaas;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\AsaasServiceProvider;
use OwnerPro\Asaas\Support\AsaasConnector;

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

it('publishes config', function () {
    $this->artisan('vendor:publish', ['--tag' => 'asaas-config']);

    expect(config('asaas.api_key'))->toBe('test-api-key');
    expect(config('asaas.environment'))->toBe('sandbox');
    expect(config('asaas.timeout'))->toBe(30);
});

it('merges default config so asaas key exists before env override', function () {
    // If mergeConfigFrom is removed, config('asaas') would only have test overrides
    // The merged config should have the package defaults available
    expect(config('asaas'))->toBeArray();
    expect(config('asaas'))->toHaveKeys(['api_key', 'environment', 'timeout']);
});

it('register method calls mergeConfigFrom', function () {
    $provider = new AsaasServiceProvider($this->app);

    // Override config with empty array
    $this->app['config']->set('asaas', []);
    expect(config('asaas'))->toBe([]);

    // Re-register should merge the config defaults back from file
    $provider->register();
    expect(config('asaas'))->toHaveKeys(['api_key', 'environment', 'timeout']);
});

it('registers publishable config with correct source path', function () {
    $paths = ServiceProvider::$publishGroups['asaas-config'] ?? [];
    expect($paths)->not->toBeEmpty();

    $configFile = array_key_first($paths);
    // The source should be __DIR__.'/../config/asaas.php' from the provider
    expect($configFile)->toEndWith('config/asaas.php');
    expect(file_exists($configFile))->toBeTrue();
});

it('loads the correct config file', function () {
    // Verifying the config path points to the right file
    // If ConcatSwitchSides or ConcatRemoveLeft happens, the path would be wrong
    $configPath = realpath(__DIR__.'/../config/asaas.php');
    expect($configPath)->not->toBeFalse();

    $configData = require $configPath;
    expect($configData)->toHaveKeys(['api_key', 'environment', 'timeout']);
});

it('Asaas::for() returns AsaasClient with config defaults', function () {
    $client = Asaas::for(apiKey: 'tenant-key');

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('Asaas::for() overrides environment', function () {
    Http::fake(['https://api.asaas.com/*' => Http::response(['id' => 'test_123'], 200)]);

    $client = Asaas::for(apiKey: 'tenant-key', environment: 'production');
    $client->payments()->find('test_123');

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.asaas.com/'));
});

it('Asaas::for() overrides timeout', function () {
    Http::fake();

    $client = Asaas::for(apiKey: 'tenant-key', timeout: 60);
    $connector = (new ReflectionProperty(AsaasClient::class, 'asaasConnector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['timeout'])->toBe(60);
});
