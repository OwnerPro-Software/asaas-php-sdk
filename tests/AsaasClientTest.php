<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;

mutates(AsaasClient::class);

it('creates an AsaasClient instance', function (): void {
    $client = new AsaasClient(apiKey: 'test-key', environment: 'sandbox', timeout: 30);

    expect($client)->toBeInstanceOf(AsaasClient::class);
});

it('throws on invalid environment', function (): void {
    new AsaasClient(apiKey: 'test-key', environment: 'staging', timeout: 30);
})->throws(InvalidArgumentException::class);

it('resolves PaymentResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->payments();
    expect($first)->toBeInstanceOf(PaymentResource::class);
    expect($client->payments())->toBe($first);
});

it('resolves PixResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->pix();
    expect($first)->toBeInstanceOf(PixResource::class);
    expect($client->pix())->toBe($first);
});

it('resolves PixTransactionResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->pixTransactions();
    expect($first)->toBeInstanceOf(PixTransactionResource::class);
    expect($client->pixTransactions())->toBe($first);
});

it('resolves TransferResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->transfers();
    expect($first)->toBeInstanceOf(TransferResource::class);
    expect($client->transfers())->toBe($first);
});

it('resolves WebhookResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->webhooks();
    expect($first)->toBeInstanceOf(WebhookResource::class);
    expect($client->webhooks())->toBe($first);
});

it('resolves InvoiceResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->invoices();
    expect($first)->toBeInstanceOf(InvoiceResource::class);
    expect($client->invoices())->toBe($first);
});

it('resolves AccountResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->accounts();
    expect($first)->toBeInstanceOf(AccountResource::class);
    expect($client->accounts())->toBe($first);
});

it('resolves CreditCardResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->creditCards();
    expect($first)->toBeInstanceOf(CreditCardResource::class);
    expect($client->creditCards())->toBe($first);
});

it('resolves BillPaymentResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->billPayments();
    expect($first)->toBeInstanceOf(BillPaymentResource::class);
    expect($client->billPayments())->toBe($first);
});

it('resolves StatementResource', function (): void {
    Http::fake();
    $client = new AsaasClient(apiKey: 'k', environment: 'sandbox', timeout: 30);
    $first = $client->statements();
    expect($first)->toBeInstanceOf(StatementResource::class);
    expect($client->statements())->toBe($first);
});
