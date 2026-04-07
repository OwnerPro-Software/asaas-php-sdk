<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\Account\Request\AccessTokenRequest;
use OwnerPro\Asaas\Account\Request\AccountRequest;
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

    $result = accountResource()->updateAccessToken('acc_123', 'tok_1', new AccessTokenRequest(
        name: 'Updated',
        enabled: false,
        expirationDate: '2027-01-01',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens/tok_1'
        && $request->method() === 'PUT');
});

it('deletes an access token', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'tok_1'], 200)]);

    $result = accountResource()->deleteAccessToken('acc_123', 'tok_1');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/accounts/acc_123/accessTokens/tok_1'
        && $request->method() === 'DELETE');
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
