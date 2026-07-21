<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use OwnerPro\Asaas\Asaas;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\AsaasServiceProvider;
use OwnerPro\Asaas\Contracts\AsaasClientContract;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\Redactable;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

mutates(AsaasServiceProvider::class, Asaas::class);

it('registers AsaasClient as singleton', function () {
    $this->app->forgetInstance(AsaasClient::class);

    (new AsaasServiceProvider($this->app))->register();

    $client1 = app(AsaasClient::class);
    $client2 = app(AsaasClient::class);

    expect($client1)->toBeInstanceOf(AsaasClient::class);
    expect($client1)->toBe($client2);
});

it('aliases AsaasClientContract to the AsaasClient singleton', function () {
    $this->app->forgetInstance(AsaasClient::class);

    (new AsaasServiceProvider($this->app))->register();

    $viaContract = app(AsaasClientContract::class);
    $viaConcrete = app(AsaasClient::class);

    expect($viaContract)->toBe($viaConcrete);
});

it('resolves AsaasClient via facade', function () {
    $client = Asaas::getFacadeRoot();

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('routes the facade through the contract so the documented fake swap applies', function () {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_fake']]);

    $this->app->instance(AsaasClientContract::class, $fake);

    expect(Asaas::getFacadeRoot())->toBe($fake);
    expect(Asaas::payments()->find('pay_1')->data['id'])->toBe('pay_fake');
});

it('serves the same instance through the facade and an injected contract', function () {
    $fake = AsaasClient::fake(['payments' => ['id' => 'pay_fake']]);

    $this->app->instance(AsaasClientContract::class, $fake);

    expect(Asaas::getFacadeRoot())->toBe(app(AsaasClientContract::class));
});

it('merges the package config defaults on register', function () {
    $this->app['config']->set('asaas', []);

    (new AsaasServiceProvider($this->app))->register();

    expect(config('asaas'))
        ->toBeArray()
        ->toHaveKeys(['api_key', 'environment', 'timeout', 'connect_timeout', 'throw_on_transport_failure']);
    expect(config('asaas.environment'))->toBe('sandbox');
    expect(config('asaas.timeout'))->toBe(30);
    expect(config('asaas.connect_timeout'))->toBe(10);
    expect(config('asaas.throw_on_transport_failure'))->toBeFalse();
});

it('passes throw_on_transport_failure config to the singleton connector', function () {
    $this->app['config']->set('asaas.throw_on_transport_failure', true);
    $this->app->forgetInstance(AsaasClient::class);
    Http::fake(['*' => fn (): never => throw new ConnectionException('timeout')]);

    app(AsaasClient::class)->payments()->find('pay_1');
})->throws(IndeterminateResultException::class);

it('Asaas::for() inherits throw_on_transport_failure from config', function () {
    $this->app['config']->set('asaas.throw_on_transport_failure', true);
    Http::fake(['*' => fn (): never => throw new ConnectionException('timeout')]);

    Asaas::for(apiKey: 'tenant-key')->payments()->find('pay_1');
})->throws(IndeterminateResultException::class);

it('Asaas::for() overrides throw_on_transport_failure per tenant', function () {
    $this->app['config']->set('asaas.throw_on_transport_failure', false);
    Http::fake(['*' => fn (): never => throw new ConnectionException('timeout')]);

    Asaas::for(apiKey: 'tenant-key', throwOnTransportFailure: true)->payments()->find('pay_1');
})->throws(IndeterminateResultException::class);

it('Asaas::for() explicit false beats enabled config', function () {
    $this->app['config']->set('asaas.throw_on_transport_failure', true);
    Http::fake(['*' => fn (): never => throw new ConnectionException('timeout')]);

    $result = Asaas::for(apiKey: 'tenant-key', throwOnTransportFailure: false)->payments()->find('pay_1');

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
});

it('falls back to disabled throw_on_transport_failure when env var is absent', function () {
    putenv('ASAAS_THROW_ON_TRANSPORT_FAILURE');
    unset($_ENV['ASAAS_THROW_ON_TRANSPORT_FAILURE'], $_SERVER['ASAAS_THROW_ON_TRANSPORT_FAILURE']);

    $config = require __DIR__.'/../config/asaas.php';

    expect($config['throw_on_transport_failure'])->toBeFalse();
});

it('publishes the config file from the correct source path on boot', function () {
    ServiceProvider::$publishes = [];

    (new AsaasServiceProvider($this->app))->boot();

    $paths = ServiceProvider::pathsToPublish(AsaasServiceProvider::class, 'asaas-config');

    expect($paths)->not->toBeEmpty();

    $sourcePath = array_key_first($paths);
    expect($sourcePath)->toEndWith('config/asaas.php');
    expect(file_exists($sourcePath))->toBeTrue();
});

it('installs the dump redaction caster on boot', function () {
    unset(AbstractCloner::$defaultCasters[Redactable::class]);

    (new AsaasServiceProvider($this->app))->boot();

    $caster = AbstractCloner::$defaultCasters[Redactable::class] ?? null;

    expect($caster)->toBeInstanceOf(Closure::class);
    expect($caster(new CreditCard('JOHN DOE', '4111111111111111', '12', '2030', '737')))
        ->toBe(['holderName' => 'JOHN DOE', 'number' => '********1111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '***']);
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

it('falls back to default timeout when ASAAS_TIMEOUT env var is empty', function () {
    putenv('ASAAS_TIMEOUT=');
    $_ENV['ASAAS_TIMEOUT'] = '';
    $_SERVER['ASAAS_TIMEOUT'] = '';

    $config = require __DIR__.'/../config/asaas.php';

    expect($config['timeout'])->toBe(30);

    putenv('ASAAS_TIMEOUT');
    unset($_ENV['ASAAS_TIMEOUT'], $_SERVER['ASAAS_TIMEOUT']);
});

it('falls back to default connect_timeout when ASAAS_CONNECT_TIMEOUT env var is empty', function () {
    putenv('ASAAS_CONNECT_TIMEOUT=');
    $_ENV['ASAAS_CONNECT_TIMEOUT'] = '';
    $_SERVER['ASAAS_CONNECT_TIMEOUT'] = '';

    $config = require __DIR__.'/../config/asaas.php';

    expect($config['connect_timeout'])->toBe(10);

    putenv('ASAAS_CONNECT_TIMEOUT');
    unset($_ENV['ASAAS_CONNECT_TIMEOUT'], $_SERVER['ASAAS_CONNECT_TIMEOUT']);
});
