<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\Account\Request\AccessTokenConfig;
use OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig;
use OwnerPro\Asaas\Account\Request\AccountRequest;
use OwnerPro\Asaas\Account\Request\EscrowConfigRequest;
use OwnerPro\Asaas\Account\Request\UpdateAccessTokenRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Environment;

mutates(AccountResource::class);

function accountConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function accountResource(): AccountResource
{
    return new AccountResource(accountConnector());
}

dataset('account_fixture', [fn (): array => [
    'id' => 'acc_123', 'name' => 'Sub Account', 'email' => 'sub@test.com',
    'cpfCnpj' => '12345678900', 'mobilePhone' => '11999999999',
    'walletId' => 'wallet_1', 'apiKey' => 'ak_123', 'accessToken' => 'at_123',
]]);

dataset('create_account_payload', [fn (): array => [
    'name' => 'Sub Account', 'email' => 'sub@test.com', 'cpfCnpj' => '12345678900',
    'mobilePhone' => '11999999999', 'incomeValue' => 5000.00,
    'address' => 'Rua X', 'addressNumber' => '100', 'province' => 'Centro',
    'postalCode' => '01001000',
]]);

it('creates a subaccount', function (array $fixture, array $payload): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = accountResource()->create($payload);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();
    expect($result->data['id'])->toBe('acc_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts'
        && $request->method() === 'POST');
})->with('account_fixture', 'create_account_payload');

it('creates a subaccount from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = accountResource()->create(new AccountRequest(
        name: 'Sub Account',
        email: 'sub@test.com',
        cpfCnpj: '12345678900',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua X',
        addressNumber: '100',
        province: 'Centro',
        postalCode: '01001000',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('acc_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts'
        && $request->method() === 'POST');
})->with('account_fixture');

it('validates required fields', function (): void {
    accountResource()->create(['name' => 'x']);
})->throws(InvalidArgumentException::class);

it('lists subaccounts', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list', 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0,
        'data' => [['id' => 'acc_1', 'name' => 'Sub']],
    ], 200)]);

    $result = accountResource()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(1);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/accounts'));
});

it('finds a subaccount', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = accountResource()->find('acc_123');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('acc_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123');
})->with('account_fixture');

it('lists access tokens', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'tok_1', 'name' => 'Main', 'enabled' => true,
    ], 200)]);

    $result = accountResource()->listAccessTokens('acc_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens');
});

it('creates an access token', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'tok_1', 'name' => 'New Token', 'enabled' => true, 'apiKey' => 'ak_new',
    ], 200)]);

    $result = accountResource()->createAccessToken('acc_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens'
        && $request->method() === 'POST');
});

it('creates an access token with name and expirationDate body', function (): void {
    Http::fake(['*' => Http::response(['id' => 'tok_1'], 200)]);

    accountResource()->createAccessToken('acc_123', ['name' => 'Onboarding', 'expirationDate' => '2026-12-31']);

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && ($request->data()['name'] ?? null) === 'Onboarding'
        && ($request->data()['expirationDate'] ?? null) === '2026-12-31');
});

it('updates an access token from array', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'tok_1', 'name' => 'Updated', 'enabled' => false,
    ], 200)]);

    $result = accountResource()->updateAccessToken('acc_123', 'tok_1', [
        'name' => 'Updated', 'enabled' => false, 'expirationDate' => '2027-01-01',
    ]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens/tok_1'
        && $request->method() === 'PUT');
});

it('updates an access token from request object', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'tok_1', 'name' => 'Updated', 'enabled' => false,
    ], 200)]);

    $result = accountResource()->updateAccessToken('acc_123', 'tok_1', new UpdateAccessTokenRequest(
        name: 'Updated',
        enabled: false,
        expirationDate: '2027-01-01',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(function ($request): bool {
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens/tok_1') {
            return false;
        }
        if ($request->method() !== 'PUT') {
            return false;
        }
        $body = $request->data();

        return ($body['name'] ?? null) === 'Updated'
            && ($body['enabled'] ?? null) === false
            && ($body['expirationDate'] ?? null) === '2027-01-01';
    });
});

it('rejects updateAccessToken array missing required fields', function (array $payload): void {
    accountResource()->updateAccessToken('acc_123', 'tok_1', $payload);
})->throws(InvalidArgumentException::class)->with([
    'missing name' => [['enabled' => true, 'expirationDate' => '2027-01-01']],
    'missing enabled' => [['name' => 'x', 'expirationDate' => '2027-01-01']],
    'missing expirationDate' => [['name' => 'x', 'enabled' => true]],
]);

it('sends accessTokenConfig on subaccount create to seed the initial key with TRANSFER permission', function (): void {
    Http::fake(['*' => Http::response(['id' => 'acc_1', 'apiKey' => 'ak_1'], 200)]);

    accountResource()->create([
        'name' => 'Sub Account',
        'email' => 'sub@test.com',
        'cpfCnpj' => '12345678900',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua X',
        'addressNumber' => '100',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => [
            'name' => 'Onboarding',
            'permissions' => [
                ['name' => 'TRANSFER', 'scope' => 'READ_WRITE'],
                ['name' => 'PIX_DEBIT', 'scope' => 'READ_WRITE'],
            ],
        ],
    ]);

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST' || $request->url() !== 'https://api-sandbox.asaas.com/v3/accounts') {
            return false;
        }
        $body = $request->data();

        return ($body['accessTokenConfig']['name'] ?? null) === 'Onboarding'
            && ($body['accessTokenConfig']['permissions'][0]['name'] ?? null) === 'TRANSFER'
            && ($body['accessTokenConfig']['permissions'][0]['scope'] ?? null) === 'READ_WRITE'
            && ($body['accessTokenConfig']['permissions'][1]['name'] ?? null) === 'PIX_DEBIT';
    });
});

it('never ships an empty accessTokenConfig as a JSON array', function (): void {
    Http::fake(['*' => Http::response(['id' => 'acc_1'], 200)]);

    accountResource()->create([
        'name' => 'Sub Account',
        'email' => 'sub@test.com',
        'cpfCnpj' => '12345678900',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua X',
        'addressNumber' => '100',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => [],
    ]);

    Http::assertSent(function ($request): bool {
        expect($request->data())->not->toHaveKey('accessTokenConfig');
        expect($request->body())->not->toContain('accessTokenConfig');

        return true;
    });
});

it('sends accessTokenConfig from DTO instances (enum cases) on subaccount create', function (): void {
    Http::fake(['*' => Http::response(['id' => 'acc_1'], 200)]);

    accountResource()->create(new AccountRequest(
        name: 'Sub Account',
        email: 'sub@test.com',
        cpfCnpj: '12345678900',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua X',
        addressNumber: '100',
        province: 'Centro',
        postalCode: '01001000',
        accessTokenConfig: new AccessTokenConfig(
            name: 'Onboarding',
            permissions: [
                new AccessTokenPermissionConfig(
                    name: AccessTokenPermission::Transfer,
                    scope: AccessTokenScope::ReadWrite,
                ),
            ],
        ),
    ));

    Http::assertSent(fn ($request): bool => ($request->data()['accessTokenConfig']['permissions'][0]['name'] ?? null) === 'TRANSFER'
        && ($request->data()['accessTokenConfig']['permissions'][0]['scope'] ?? null) === 'READ_WRITE');
});

it('sends permissions on updateAccessToken to widen an existing key', function (): void {
    Http::fake(['*' => Http::response(['id' => 'tok_1'], 200)]);

    accountResource()->updateAccessToken('acc_123', 'tok_1', new UpdateAccessTokenRequest(
        name: 'Widened',
        enabled: true,
        expirationDate: '2027-01-01',
        permissions: [
            new AccessTokenPermissionConfig(
                name: AccessTokenPermission::Transfer,
                scope: AccessTokenScope::ReadWrite,
            ),
            new AccessTokenPermissionConfig(
                name: AccessTokenPermission::Webhook,
                scope: AccessTokenScope::Read,
            ),
        ],
    ));

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'PUT') {
            return false;
        }
        $body = $request->data();

        return ($body['permissions'][0]['name'] ?? null) === 'TRANSFER'
            && ($body['permissions'][0]['scope'] ?? null) === 'READ_WRITE'
            && ($body['permissions'][1]['name'] ?? null) === 'WEBHOOK'
            && ($body['permissions'][1]['scope'] ?? null) === 'READ';
    });
});

it('sends permissions on createAccessToken when provided via raw array', function (): void {
    Http::fake(['*' => Http::response(['id' => 'tok_1'], 200)]);

    accountResource()->createAccessToken('acc_123', [
        'name' => 'Limited',
        'permissions' => [
            ['name' => 'PAYMENT', 'scope' => 'READ_WRITE'],
        ],
    ]);

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        $body = $request->data();

        return ($body['name'] ?? null) === 'Limited'
            && ($body['permissions'][0]['name'] ?? null) === 'PAYMENT'
            && ($body['permissions'][0]['scope'] ?? null) === 'READ_WRITE';
    });
});

it('deletes an access token', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'tok_1'], 200)]);

    $result = accountResource()->deleteAccessToken('acc_123', 'tok_1');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens/tok_1'
        && $request->method() === 'DELETE');
});

it('treats 204 No Content as success on deleteAccessToken', function (): void {
    Http::fake(['*' => Http::response('', 204)]);

    $result = accountResource()->deleteAccessToken('acc_123', 'tok_1');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
    expect($result->response->status())->toBe(204);
});

it('iterates all accounts lazily', function (): void {
    $page1 = [
        'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 2, 'offset' => 0,
        'data' => [['id' => 'acc_1', 'name' => 'Sub 1'], ['id' => 'acc_2', 'name' => 'Sub 2']],
    ];
    $page2 = [
        'object' => 'list', 'hasMore' => false, 'totalCount' => 3, 'limit' => 2, 'offset' => 2,
        'data' => [['id' => 'acc_3', 'name' => 'Sub 3']],
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $items = iterator_to_array(accountResource()->all(['limit' => 2]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeArray();
    expect($items[2]['id'])->toBe('acc_3');
});

it('returns failure on API error', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'Unauthorized']]], 401)]);

    $result = accountResource()->find('acc_invalid');

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(401);
});

// --- escrow ---

it('gets escrow config for a subaccount', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 30, 'enabled' => true], 200)]);

    $result = accountResource()->escrowConfig('acc_123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/escrow');
});

it('sets escrow config for a subaccount', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 45, 'enabled' => true, 'isFeePayer' => true], 200)]);

    $result = accountResource()->setEscrowConfig('acc_123', [
        'daysToExpire' => 45,
        'enabled' => true,
        'isFeePayer' => true,
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($r): bool {
        if ($r->method() !== 'POST') {
            return false;
        }
        if ($r->url() !== 'https://api-sandbox.asaas.com/v3/accounts/acc_123/escrow') {
            return false;
        }
        $body = $r->data();

        return ($body['daysToExpire'] ?? null) === 45
            && ($body['enabled'] ?? null) === true
            && ($body['isFeePayer'] ?? null) === true;
    });
});

it('accepts an EscrowConfigRequest DTO on setEscrowConfig', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    accountResource()->setEscrowConfig(
        'acc_123',
        new EscrowConfigRequest(daysToExpire: 15),
    );

    Http::assertSent(fn ($r): bool => ($r->data()['daysToExpire'] ?? null) === 15);
});

it('rejects empty accountId on escrowConfig', function (): void {
    accountResource()->escrowConfig('');
})->throws(InvalidArgumentException::class);

it('gets the default escrow config', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 30], 200)]);

    $result = accountResource()->defaultEscrowConfig();

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/accounts/escrow');
});

it('sets the default escrow config', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 60], 200)]);

    accountResource()->setDefaultEscrowConfig(['daysToExpire' => 60]);

    Http::assertSent(function ($r): bool {
        if ($r->method() !== 'POST' || $r->url() !== 'https://api-sandbox.asaas.com/v3/accounts/escrow') {
            return false;
        }

        return ($r->data()['daysToExpire'] ?? null) === 60;
    });
});

it('omits an empty permissions list so the key keeps its default scope', function (string $method, string $verb, array $payload): void {
    Http::fake(['*' => Http::response(['id' => 'tok_1'], 200)]);

    accountResource()->{$method}('acc_123', ...$payload);

    Http::assertSent(function ($request) use ($verb): bool {
        expect($request->data())->not->toHaveKey('permissions');

        return $request->method() === $verb;
    });
})->with([
    'createAccessToken' => ['createAccessToken', 'POST', [['name' => 'Onboarding', 'permissions' => []]]],
    'updateAccessToken' => ['updateAccessToken', 'PUT', ['tok_1', ['name' => 'Widened', 'enabled' => true, 'expirationDate' => '2027-01-01', 'permissions' => []]]],
]);
