<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Payment\LeanPaymentResource;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;
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

it('lists lean payments via paginate', function (): void {
    Http::fake(['*' => Http::response([
        'data' => [['id' => 'pay_1']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0,
    ], 200)]);

    $result = leanPaymentResource()->list(['status' => 'CONFIRMED']);

    expect($result->success)->toBeTrue();
    expect($result->data[0]['id'])->toBe('pay_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/lean/payments')
        && str_contains($r->url(), 'status=CONFIRMED'));
});

it('updates a lean payment via PUT /lean/payments/{id}', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_1'], 200)]);

    leanPaymentResource()->update('pay_1', new UpdatePaymentRequest(
        value: 250.00,
        dueDate: '2026-07-01',
    ));

    Http::assertSent(function ($r): bool {
        if ($r->method() !== 'PUT' || $r->url() !== 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1') {
            return false;
        }
        $body = $r->data();

        return ($body['value'] ?? null) === 250.00 && ($body['dueDate'] ?? null) === '2026-07-01';
    });
});

it('updates a lean payment from array payload', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_1'], 200)]);

    leanPaymentResource()->update('pay_1', ['value' => 100.0]);

    Http::assertSent(fn ($r): bool => $r->method() === 'PUT'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1'
        && ($r->data()['value'] ?? null) === 100.0);
});

it('rejects empty id on update', function (): void {
    leanPaymentResource()->update('', ['value' => 1.0]);
})->throws(InvalidArgumentException::class);

it('deletes a lean payment', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'pay_1'], 200)]);

    $result = leanPaymentResource()->delete('pay_1');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($r): bool => $r->method() === 'DELETE'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/lean/payments/pay_1');
});

it('rejects empty id on delete', function (): void {
    leanPaymentResource()->delete('');
})->throws(InvalidArgumentException::class);

it('iterates all lean payments lazily', function (): void {
    $page1 = [
        'data' => [['id' => 'pay_1'], ['id' => 'pay_2']], 'hasMore' => true, 'totalCount' => 3, 'limit' => 2, 'offset' => 0,
    ];
    $page2 = [
        'data' => [['id' => 'pay_3']], 'hasMore' => false, 'totalCount' => 3, 'limit' => 2, 'offset' => 2,
    ];

    Http::fakeSequence()->push($page1, 200)->push($page2, 200);

    $items = iterator_to_array(leanPaymentResource()->all(['limit' => 2]));

    expect($items)->toHaveCount(3);
    expect($items[2]['id'])->toBe('pay_3');
});
