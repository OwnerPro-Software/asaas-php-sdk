<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig;
use OwnerPro\Asaas\Account\Request\AccessTokenRequest;

mutates(AccessTokenRequest::class);

it('builds from array with scalar fields only', function (): void {
    $request = AccessTokenRequest::fromArray([
        'name' => 'Onboarding',
        'enabled' => true,
        'expirationDate' => '2026-12-31 23:59:59',
    ]);

    expect($request->name)->toBe('Onboarding');
    expect($request->enabled)->toBeTrue();
    expect($request->expirationDate)->toBe('2026-12-31 23:59:59');
    expect($request->permissions)->toBeNull();
});

it('coerces nested permissions from arrays', function (): void {
    $request = AccessTokenRequest::fromArray([
        'name' => 'Limited',
        'permissions' => [
            ['name' => 'PAYMENT', 'scope' => 'READ_WRITE'],
            ['name' => 'TRANSFER', 'scope' => 'READ'],
        ],
    ]);

    expect($request->permissions)->toHaveCount(2);
    expect($request->permissions[0])->toBeInstanceOf(AccessTokenPermissionConfig::class);
    expect($request->permissions[0]->name)->toBe('PAYMENT');
    expect($request->permissions[1]->scope)->toBe('READ');
});

it('serializes permissions to the documented wire shape', function (): void {
    $request = new AccessTokenRequest(
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

it('omits permissions from toArray when null', function (): void {
    $request = new AccessTokenRequest(name: 'OnlyName');

    expect($request->toArray())->toBe(['name' => 'OnlyName']);
});
