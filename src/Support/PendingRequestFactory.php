<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

/**
 * Chooses where the underlying `PendingRequest` comes from.
 *
 * The distinction matters beyond wiring: a request built by Laravel's HTTP
 * factory honours `Http::fake()`, `Http::preventStrayRequests()` and any
 * registered middleware, while a bare `new PendingRequest` builds its own
 * Guzzle client and answers to none of them.
 *
 * @internal Construction detail of {@see AsaasConnector}.
 */
final class PendingRequestFactory
{
    /**
     * Prefers the framework factory whenever one is reachable.
     *
     * `AsaasClient::for()` is the documented way to hold a per-tenant API key,
     * so it is routinely called from inside a booted Laravel app. Building a
     * detached request there is a trap: a suite that fakes HTTP would still
     * issue live calls against Asaas with real credentials. Outside Laravel
     * there is no factory to reach, and the detached request is the only option.
     */
    public static function standalone(): PendingRequest
    {
        $container = Facade::getFacadeApplication();

        if ($container instanceof Container && $container->bound(Factory::class)) {
            return self::laravel();
        }

        return new PendingRequest;
    }

    public static function laravel(): PendingRequest
    {
        return Http::createPendingRequest();
    }
}
