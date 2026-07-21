<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\Request\AccessTokenConfig;
use OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig;
use OwnerPro\Asaas\Account\Request\AccountRequest;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;

mutates(AccountRequest::class);

it('creates from array with all fields', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'birthDate' => '1990-01-01',
        'companyType' => 'MEI',
        'phone' => '1133334444',
        'complement' => 'Apt 4',
        'site' => 'https://example.com',
    ]);

    expect($request->name)->toBe('John Doe');
    expect($request->email)->toBe('john@example.com');
    expect($request->cpfCnpj)->toBe('12345678901');
    expect($request->mobilePhone)->toBe('11999999999');
    expect($request->incomeValue)->toBe(5000.00);
    expect($request->address)->toBe('Rua Exemplo');
    expect($request->addressNumber)->toBe('123');
    expect($request->province)->toBe('Centro');
    expect($request->postalCode)->toBe('01001000');
    expect($request->birthDate)->toBe('1990-01-01');
    expect($request->companyType)->toBe('MEI');
    expect($request->phone)->toBe('1133334444');
    expect($request->complement)->toBe('Apt 4');
    expect($request->site)->toBe('https://example.com');
});

it('creates from array with only required fields', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
    ]);

    expect($request->birthDate)->toBeNull();
    expect($request->companyType)->toBeNull();
    expect($request->phone)->toBeNull();
    expect($request->complement)->toBeNull();
    expect($request->site)->toBeNull();
});

it('masks sensitive data in debug info', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
        birthDate: '1990-01-01',
        companyType: 'MEI',
        phone: '1133334444',
        complement: 'Apt 4',
        site: 'https://example.com',
    );

    $debug = $request->__debugInfo();

    expect($debug['name'])->toBe('John Doe');
    expect($debug['email'])->toBe('***');
    expect($debug['cpfCnpj'])->toBe('********901');
    expect($debug['mobilePhone'])->toBe('***');
    expect($debug['incomeValue'])->toBe(5000.00);
    expect($debug['address'])->toBe('Rua Exemplo');
    expect($debug['addressNumber'])->toBe('123');
    expect($debug['province'])->toBe('Centro');
    expect($debug['postalCode'])->toBe('01001000');
    expect($debug['birthDate'])->toBe('***');
    expect($debug['companyType'])->toBe('MEI');
    expect($debug['phone'])->toBe('***');
    expect($debug['complement'])->toBe('Apt 4');
    expect($debug['site'])->toBe('https://example.com');
});

it('shows null optionals as null in debug info', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
    );

    $debug = $request->__debugInfo();

    expect($debug['birthDate'])->toBeNull();
    expect($debug['phone'])->toBeNull();
    expect($debug['loginEmail'])->toBeNull();
    expect($debug)->toHaveKey('webhooks');
    expect($debug['webhooks'])->toBeNull();
    expect($debug)->toHaveKey('accessTokenConfig');
    expect($debug['accessTokenConfig'])->toBeNull();
});

it('masks loginEmail and surfaces webhooks/accessTokenConfig in debug info when set', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
        loginEmail: 'login@example.com',
        webhooks: [new CreateWebhookRequest(url: 'https://x.com', email: 'a@b.com')],
        accessTokenConfig: new AccessTokenConfig(
            name: 'Onboarding',
            permissions: [
                new AccessTokenPermissionConfig(
                    name: AccessTokenPermission::Payment,
                    scope: AccessTokenScope::ReadWrite,
                ),
            ],
        ),
    );

    $debug = $request->__debugInfo();

    expect($debug['loginEmail'])->toBe('***');
    expect($debug['webhooks'])->toHaveCount(1);
    expect($debug['accessTokenConfig'])->toBeInstanceOf(AccessTokenConfig::class);
});

it('masks short cpfCnpj without negative repeat', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '01',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
    );

    expect($request->__debugInfo()['cpfCnpj'])->toBe('**');
});

it('masks sensitive data in json serialization', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua Exemplo',
        addressNumber: '123',
        province: 'Centro',
        postalCode: '01001000',
        birthDate: '1990-01-01',
        phone: '1133334444',
    );

    $json = json_decode(json_encode($request), true);

    expect($json['email'])->toBe('***');
    expect($json['cpfCnpj'])->toBe('********901');
    expect($json['mobilePhone'])->toBe('***');
    expect($json['birthDate'])->toBe('***');
    expect($json['phone'])->toBe('***');
});

it('throws when required field is missing', function (string $missingField): void {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
    ];

    unset($data[$missingField]);

    AccountRequest::fromArray($data);
})->throws(InvalidArgumentException::class)->with([
    'name',
    'email',
    'cpfCnpj',
    'mobilePhone',
    'incomeValue',
    'address',
    'addressNumber',
    'province',
    'postalCode',
]);

it('accepts loginEmail and persists it', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'loginEmail' => 'login@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
    ]);

    expect($request->loginEmail)->toBe('login@example.com');
});

it('coerces webhooks array to CreateWebhookRequest list', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua Exemplo',
        'addressNumber' => '123',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'webhooks' => [
            ['url' => 'https://hooks.example.com/x', 'email' => 'ops@example.com'],
        ],
    ]);

    expect($request->webhooks)->toHaveCount(1);
    expect($request->webhooks[0])->toBeInstanceOf(CreateWebhookRequest::class);
    expect($request->webhooks[0]->url)->toBe('https://hooks.example.com/x');
});

it('serializes loginEmail and webhooks in toArray when set', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua',
        addressNumber: '1',
        province: 'Centro',
        postalCode: '01001000',
        loginEmail: 'login@example.com',
        webhooks: [new CreateWebhookRequest(url: 'https://x.com', email: 'a@b.com')],
    );

    $array = $request->toArray();

    expect($array['loginEmail'])->toBe('login@example.com');
    expect($array['webhooks'])->toBe([[
        'url' => 'https://x.com',
        'email' => 'a@b.com',
        'interrupted' => false,
    ]]);
});

it('accepts accessTokenConfig as raw array and coerces nested permissions', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => [
            'name' => 'Onboarding',
            'permissions' => [
                ['name' => 'TRANSFER', 'scope' => 'READ_WRITE'],
                ['name' => 'WEBHOOK', 'scope' => 'READ'],
            ],
        ],
    ]);

    expect($request->accessTokenConfig)->toBeInstanceOf(AccessTokenConfig::class);
    expect($request->accessTokenConfig->name)->toBe('Onboarding');
    expect($request->accessTokenConfig->permissions)->toHaveCount(2);
    expect($request->accessTokenConfig->permissions[0])->toBeInstanceOf(AccessTokenPermissionConfig::class);
});

it('serializes accessTokenConfig to the documented Asaas wire shape', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua',
        addressNumber: '1',
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
    );

    expect($request->toArray()['accessTokenConfig'])->toBe([
        'permissions' => [
            ['name' => 'TRANSFER', 'scope' => 'READ_WRITE'],
        ],
        'name' => 'Onboarding',
    ]);
});

it('omits an accessTokenConfig that carries nothing to configure', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => [],
    ]);

    expect($request->accessTokenConfig)->toBeNull();
    expect($request->toArray())->not->toHaveKey('accessTokenConfig');
});

it('omits an empty accessTokenConfig instance too', function (): void {
    $request = new AccountRequest(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        mobilePhone: '11999999999',
        incomeValue: 5000.00,
        address: 'Rua',
        addressNumber: '1',
        province: 'Centro',
        postalCode: '01001000',
        accessTokenConfig: new AccessTokenConfig,
    );

    expect($request->accessTokenConfig)->toBeNull();
    expect($request->toArray())->not->toHaveKey('accessTokenConfig');
});

it('omits an accessTokenConfig whose permissions list is empty', function (): void {
    // `{"permissions": []}` has no documented meaning: omitting the field mints
    // the all-permissions READ_WRITE key, so shipping the empty list would leave
    // the subaccount's initial key in an undefined permission state.
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => ['permissions' => []],
    ]);

    expect($request->accessTokenConfig)->toBeNull();
    expect($request->toArray())->not->toHaveKey('accessTokenConfig');
});

it('keeps an accessTokenConfig carrying a name but no permissions', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => ['name' => 'Onboarding', 'permissions' => []],
    ]);

    expect($request->accessTokenConfig)->not->toBeNull();
    expect($request->toArray()['accessTokenConfig'])->toEqual(['name' => 'Onboarding', 'permissions' => []]);
});

it('keeps an accessTokenConfig carrying only permissions', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => [
            'permissions' => [['name' => 'TRANSFER', 'scope' => 'READ_WRITE']],
        ],
    ]);

    expect($request->toArray()['accessTokenConfig'])->toBe([
        'permissions' => [['name' => 'TRANSFER', 'scope' => 'READ_WRITE']],
    ]);
});

it('keeps an accessTokenConfig carrying only a name', function (): void {
    $request = AccountRequest::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'mobilePhone' => '11999999999',
        'incomeValue' => 5000.00,
        'address' => 'Rua',
        'addressNumber' => '1',
        'province' => 'Centro',
        'postalCode' => '01001000',
        'accessTokenConfig' => ['name' => 'Onboarding'],
    ]);

    expect($request->toArray()['accessTokenConfig'])->toBe(['name' => 'Onboarding']);
});

it('cannot be serialized', function (): void {
    $request = new AccountRequest(
        name: 'Acme',
        email: 'acme@example.com',
        cpfCnpj: '12345678900',
        mobilePhone: '11999990000',
        incomeValue: 1000.0,
        address: 'Av Paulista',
        addressNumber: '1',
        province: 'Centro',
        postalCode: '01001000',
    );

    serialize($request);
})->throws(LogicException::class);
