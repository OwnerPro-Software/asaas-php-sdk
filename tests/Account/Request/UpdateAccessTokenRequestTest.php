<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig;
use OwnerPro\Asaas\Account\Request\UpdateAccessTokenRequest;

mutates(UpdateAccessTokenRequest::class);

it('requires name, enabled and expirationDate', function (): void {
    $request = new UpdateAccessTokenRequest(
        name: 'Onboarding',
        enabled: true,
        expirationDate: '2026-12-31 23:59:59',
    );

    expect($request->name)->toBe('Onboarding');
    expect($request->enabled)->toBeTrue();
    expect($request->expirationDate)->toBe('2026-12-31 23:59:59');
    expect($request->permissions)->toBeNull();
});

it('builds from array with all required fields', function (): void {
    $request = UpdateAccessTokenRequest::fromArray([
        'name' => 'Updated',
        'enabled' => false,
        'expirationDate' => '2027-01-01',
    ]);

    expect($request->name)->toBe('Updated');
    expect($request->enabled)->toBeFalse();
    expect($request->expirationDate)->toBe('2027-01-01');
});

it('throws when name is missing on fromArray', function (): void {
    UpdateAccessTokenRequest::fromArray([
        'enabled' => true,
        'expirationDate' => '2027-01-01',
    ]);
})->throws(InvalidArgumentException::class, 'name is required');

it('throws when enabled is missing on fromArray', function (): void {
    UpdateAccessTokenRequest::fromArray([
        'name' => 'x',
        'expirationDate' => '2027-01-01',
    ]);
})->throws(InvalidArgumentException::class, 'enabled is required');

it('throws when expirationDate is missing on fromArray', function (): void {
    UpdateAccessTokenRequest::fromArray([
        'name' => 'x',
        'enabled' => true,
    ]);
})->throws(InvalidArgumentException::class, 'expirationDate is required');

it('coerces nested permissions from arrays on update', function (): void {
    $request = UpdateAccessTokenRequest::fromArray([
        'name' => 'Limited',
        'enabled' => true,
        'expirationDate' => '2027-01-01',
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

it('serialises enum permissions to wire shape on update', function (): void {
    $request = new UpdateAccessTokenRequest(
        name: 'Onboarding',
        enabled: true,
        expirationDate: '2027-01-01',
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
        'name' => 'Onboarding',
        'enabled' => true,
        'expirationDate' => '2027-01-01',
    ]);
});
