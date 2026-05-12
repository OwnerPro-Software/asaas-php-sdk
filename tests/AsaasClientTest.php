<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\Account\MyAccountResource;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\FiscalInfo\FiscalInfoResource;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Payment\LeanPaymentResource;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticResource;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;

mutates(AsaasClient::class);

it('creates client via for() with defaults', function (): void {
    $client = AsaasClient::for(apiKey: 'test-key');

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('for() uses default timeout of 30', function (): void {
    $client = AsaasClient::for(apiKey: 'test-key');

    $connector = (new ReflectionProperty(AsaasClient::class, 'connector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['timeout'])->toBe(30);
});

it('for() uses default connect timeout of 10', function (): void {
    $client = AsaasClient::for(apiKey: 'test-key');

    $connector = (new ReflectionProperty(AsaasClient::class, 'connector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['connect_timeout'])->toBe(10);
});

it('for() accepts custom connect timeout', function (): void {
    $client = AsaasClient::for(apiKey: 'test-key', connectTimeout: 5);

    $connector = (new ReflectionProperty(AsaasClient::class, 'connector'))->getValue($client);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options['connect_timeout'])->toBe(5);
});

it('creates client via for() with overrides', function (): void {
    $client = AsaasClient::for(apiKey: 'test-key', environment: Environment::Production, timeout: 60);

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('creates client via for() with string environment', function (): void {
    $client = AsaasClient::for(apiKey: 'test-key', environment: 'production');

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('for() throws on invalid environment string', function (): void {
    AsaasClient::for(apiKey: 'test-key', environment: 'staging');
})->throws(ValueError::class);

it('creates client via DI constructor', function (): void {
    Http::fake();
    $connector = AsaasConnector::forLaravel('k', Environment::Sandbox, 30);
    $client = new AsaasClient($connector);

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('hides the connector from debug output', function (): void {
    $client = AsaasClient::for(apiKey: 'sk_live_super_secret_key_123');

    $debug = $client->__debugInfo();

    expect($debug)->toHaveKey('resources');
    expect($debug['resources'])->toBe([
        'payments',
        'pix',
        'pixTransactions',
        'pixAutomatic',
        'transfers',
        'webhooks',
        'invoices',
        'accounts',
        'myAccount',
        'creditCards',
        'billPayments',
        'statements',
        'fiscalInfo',
        'leanPayments',
    ]);
    expect($debug)->not->toHaveKey('connector');
    expect(print_r($client, true))->not->toContain('sk_live_super_secret_key_123');
});

it('resolves PaymentResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->payments();
    expect($first)->toBeInstanceOf(PaymentResource::class);
    expect($client->payments())->toBe($first);
});

it('resolves PixResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->pix();
    expect($first)->toBeInstanceOf(PixResource::class);
    expect($client->pix())->toBe($first);
});

it('resolves PixTransactionResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->pixTransactions();
    expect($first)->toBeInstanceOf(PixTransactionResource::class);
    expect($client->pixTransactions())->toBe($first);
});

it('resolves PixAutomaticResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->pixAutomatic();
    expect($first)->toBeInstanceOf(PixAutomaticResource::class);
    expect($client->pixAutomatic())->toBe($first);
});

it('resolves TransferResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->transfers();
    expect($first)->toBeInstanceOf(TransferResource::class);
    expect($client->transfers())->toBe($first);
});

it('resolves WebhookResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->webhooks();
    expect($first)->toBeInstanceOf(WebhookResource::class);
    expect($client->webhooks())->toBe($first);
});

it('resolves InvoiceResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->invoices();
    expect($first)->toBeInstanceOf(InvoiceResource::class);
    expect($client->invoices())->toBe($first);
});

it('resolves AccountResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->accounts();
    expect($first)->toBeInstanceOf(AccountResource::class);
    expect($client->accounts())->toBe($first);
});

it('resolves CreditCardResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->creditCards();
    expect($first)->toBeInstanceOf(CreditCardResource::class);
    expect($client->creditCards())->toBe($first);
});

it('resolves BillPaymentResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->billPayments();
    expect($first)->toBeInstanceOf(BillPaymentResource::class);
    expect($client->billPayments())->toBe($first);
});

it('resolves StatementResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->statements();
    expect($first)->toBeInstanceOf(StatementResource::class);
    expect($client->statements())->toBe($first);
});

it('resolves FiscalInfoResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->fiscalInfo();
    expect($first)->toBeInstanceOf(FiscalInfoResource::class);
    expect($client->fiscalInfo())->toBe($first);
});

it('resolves LeanPaymentResource', function (): void {
    Http::fake();
    $client = new AsaasClient(AsaasConnector::forLaravel('k', Environment::Sandbox, 30));
    $first = $client->leanPayments();
    expect($first)->toBeInstanceOf(LeanPaymentResource::class);
    expect($client->leanPayments())->toBe($first);
});

it('exposes a MyAccountResource through myAccount()', function (): void {
    $client = AsaasClient::for('test-key', Environment::Sandbox);

    expect($client->myAccount())->toBeInstanceOf(MyAccountResource::class);
});

it('caches the MyAccountResource instance', function (): void {
    $client = AsaasClient::for('test-key', Environment::Sandbox);

    expect($client->myAccount())->toBe($client->myAccount());
});

it('lists myAccount in __debugInfo resources', function (): void {
    $client = AsaasClient::for('test-key', Environment::Sandbox);

    expect($client->__debugInfo()['resources'])->toContain('myAccount');
});

it('fake() forwards a string environment through to FakeAsaasClient', function (): void {
    $fake = AsaasClient::fake([], 'production');

    $fake->stub('payments/*', ['id' => 'pay_1']);
    $fake->payments()->find('pay_1');

    // String 'production' is resolved to Environment::Production inside
    // FakeAsaasClient::__construct (where the coercion now lives) — observable
    // through the production baseUrl in the recorded request.
    expect($fake->recorded()[0][0]->url())
        ->toBe('https://api.asaas.com/v3/payments/pay_1');
});

it('fake() throws ValueError on invalid environment string', function (): void {
    AsaasClient::fake([], 'staging');
})->throws(ValueError::class);
