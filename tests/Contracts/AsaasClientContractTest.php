<?php

declare(strict_types=1);

use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Contracts\AsaasClientContract;

it('AsaasClient implements AsaasClientContract', function (): void {
    expect(is_subclass_of(AsaasClient::class, AsaasClientContract::class))->toBeTrue();
});

it('contract exposes every resource accessor that the client exposes', function (): void {
    $clientMethods = array_map(
        static fn (ReflectionMethod $m): string => $m->getName(),
        (new ReflectionClass(AsaasClient::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    $resourceAccessors = array_values(array_filter(
        $clientMethods,
        static fn (string $name): bool => ! str_starts_with($name, '__')
            && $name !== 'for'
            && $name !== 'fake',
    ));

    $contractMethods = array_map(
        static fn (ReflectionMethod $m): string => $m->getName(),
        (new ReflectionClass(AsaasClientContract::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    sort($resourceAccessors);
    sort($contractMethods);

    expect($contractMethods)->toEqual($resourceAccessors);
});
