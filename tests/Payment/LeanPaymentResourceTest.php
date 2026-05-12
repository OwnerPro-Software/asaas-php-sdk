<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Payment\LeanPaymentResource;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;

mutates(LeanPaymentResource::class);

function leanPaymentResource(): LeanPaymentResource
{
    return new LeanPaymentResource(AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30));
}

it('creates a lean payment via /lean/payments', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_1'], 200)]);

    $result = leanPaymentResource()->create([
        'customer' => 'cus_1',
        'billingType' => 'PIX',
        'value' => 100.0,
        'dueDate' => '2026-06-01',
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments'
        && ($r->data()['billingType'] ?? null) === 'PIX');
});

it('creates a lean payment with credit card via /lean/payments/', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_1'], 200)]);

    leanPaymentResource()->createWithCreditCard(new CreatePaymentRequest(
        customer: 'cus_1',
        billingType: 'CREDIT_CARD',
        value: 100.0,
        dueDate: '2026-06-01',
    ));

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/');
});

it('finds a lean payment', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_1'], 200)]);

    leanPaymentResource()->find('pay_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1');
});

it('captures an authorized lean payment', function (): void {
    Http::fake(['*' => Http::response(['captured' => true], 200)]);

    leanPaymentResource()->captureAuthorized('pay_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1/captureAuthorizedPayment');
});

it('restores a deleted lean payment', function (): void {
    Http::fake(['*' => Http::response(['restored' => true], 200)]);

    leanPaymentResource()->restore('pay_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1/restore');
});

it('refunds a lean payment with default empty body', function (): void {
    Http::fake(['*' => Http::response(['refunded' => true], 200)]);

    leanPaymentResource()->refund('pay_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1/refund');
});

it('receives a lean payment in cash with optional fields', function (): void {
    Http::fake(['*' => Http::response(['received' => true], 200)]);

    leanPaymentResource()->receiveInCash('pay_1', [
        'paymentDate' => '2026-06-01',
        'value' => 50.0,
    ]);

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1/receiveInCash'
        && ($r->data()['value'] ?? null) === 50.0);
});

it('undoes a lean cash receipt', function (): void {
    Http::fake(['*' => Http::response(['undone' => true], 200)]);

    leanPaymentResource()->undoReceivedInCash('pay_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'POST'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1/undoReceivedInCash');
});

it('rejects empty id on find', function (): void {
    leanPaymentResource()->find('');
})->throws(InvalidArgumentException::class);

it('rejects empty id on refund', function (): void {
    leanPaymentResource()->refund('');
})->throws(InvalidArgumentException::class);
