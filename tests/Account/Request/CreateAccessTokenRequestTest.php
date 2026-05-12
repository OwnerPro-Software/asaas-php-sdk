<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig;
use OwnerPro\Asaas\Account\Request\CreateAccessTokenRequest;

mutates(CreateAccessTokenRequest::class);

it('builds an empty request when POST body is not needed', function (): void {
    $request = new CreateAccessTokenRequest;

    expect($request->name)->toBeNull();
    expect($request->expirationDate)->toBeNull();
    expect($request->permissions)->toBeNull();
    expect($request->toArray())->toBe([]);
});

it('builds from array with name and expirationDate', function (): void {
    $request = CreateAccessTokenRequest::fromArray([
        'name' => 'Onboarding',
        'expirationDate' => '2026-12-31 23:59:59',
    ]);

    expect($request->name)->toBe('Onboarding');
    expect($request->expirationDate)->toBe('2026-12-31 23:59:59');
    expect($request->permissions)->toBeNull();
});

it('coerces nested permissions from arrays on create', function (): void {
    $request = CreateAccessTokenRequest::fromArray([
        'name' => 'Limited',
        'permissions' => [
            ['name' => 'PAYMENT', 'scope' => 'READ_WRITE'],
        ],
    ]);

    expect($request->permissions)->toHaveCount(1);
    expect($request->permissions[0])->toBeInstanceOf(AccessTokenPermissionConfig::class);
    expect($request->permissions[0]->name)->toBe('PAYMENT');
});

it('serialises enum permissions on create body', function (): void {
    $request = new CreateAccessTokenRequest(
        permissions: [
            new AccessTokenPermissionConfig(
                name: AccessTokenPermission::Transfer,
                scope: AccessTokenScope::ReadWrite,
            ),
        ],
    );

    expect($request->toArray())->toBe([
        'permissions' => [
            ['name' => 'TRANSFER', 'scope' => 'READ_WRITE'],
        ],
    ]);
});
