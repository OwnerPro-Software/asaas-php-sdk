<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Payment\BillingInfoDTO;
use OwnerPro\Asaas\Payment\CreatePaymentData;
use OwnerPro\Asaas\Payment\IdentificationFieldDTO;
use OwnerPro\Asaas\Payment\PaymentDTO;
use OwnerPro\Asaas\Payment\PaymentLimitsDTO;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Payment\PaymentSimulationDTO;
use OwnerPro\Asaas\Payment\PaymentStatusDTO;
use OwnerPro\Asaas\Payment\PixQrCodeDTO;
use OwnerPro\Asaas\Payment\UpdatePaymentData;
use OwnerPro\Asaas\Payment\ViewingInfoDTO;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

mutates(PaymentResource::class);

function paymentConnector(): AsaasConnector
{
    return new AsaasConnector('test-key', 'sandbox', 30);
}

function paymentResource(): PaymentResource
{
    return new PaymentResource(paymentConnector());
}

dataset('payment_fixture', [fn (): array => json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_created.json'), true)]);
dataset('payment_list_fixture', [fn (): array => json_decode(file_get_contents(__DIR__.'/../Fixtures/payment_list.json'), true)]);

// --- create ---

it('creates a payment from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->create([
        'customer' => 'cus_456',
        'billingType' => 'PIX',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
    ]);

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentDTO::class);
    expect($result->data->id)->toBe('pay_abc123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
})->with('payment_fixture');

it('creates a payment from DTO', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->create(new CreatePaymentData(
        customer: 'cus_456',
        billingType: 'PIX',
        value: 150.00,
        dueDate: '2026-04-01',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pay_abc123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
})->with('payment_fixture');

it('validates required fields when creating from array', function (): void {
    paymentResource()->create(['customer' => 'cus_456']);
})->throws(InvalidArgumentException::class, "Field 'billingType' is required.");

// --- find ---

it('finds a payment by id', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->find('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('pay_abc123');
    expect($result->data->billingType)->toBe('PIX');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123');
})->with('payment_fixture');

// --- list ---

it('lists payments with pagination', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->list(['limit' => 10]);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0])->toBeInstanceOf(PaymentDTO::class);
    expect($result->totalCount)->toBe(50);
    expect($result->hasMore)->toBeTrue();

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/payments'));
})->with('payment_list_fixture');

// --- update ---

it('updates a payment from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->update('pay_abc123', ['value' => 200.00]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123'
        && $request->method() === 'PUT');
})->with('payment_fixture');

it('updates a payment from DTO', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->update('pay_abc123', new UpdatePaymentData(value: 200.00));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123'
        && $request->method() === 'PUT');
})->with('payment_fixture');

// --- delete ---

it('deletes a payment', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'pay_abc123'], 200)]);

    $result = paymentResource()->delete('pay_abc123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123'
        && $request->method() === 'DELETE');
});

// --- refund ---

it('refunds a payment', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->refund('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/refund'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- restore ---

it('restores a deleted payment', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->restore('pay_abc123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/restore'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- status ---

it('gets payment status', function (): void {
    Http::fake(['*' => Http::response(['status' => 'CONFIRMED'], 200)]);

    $result = paymentResource()->status('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentStatusDTO::class);
    expect($result->data->status)->toBe('CONFIRMED');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/status');
});

// --- pixQrCode ---

it('gets pix qr code for payment', function (): void {
    Http::fake(['*' => Http::response([
        'encodedImage' => 'base64...',
        'payload' => '00020126...',
        'expirationDate' => '2026-04-01',
    ], 200)]);

    $result = paymentResource()->pixQrCode('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PixQrCodeDTO::class);
    expect($result->data->payload)->toBe('00020126...');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/pixQrCode');
});

// --- identificationField ---

it('gets bank slip identification field', function (): void {
    Http::fake(['*' => Http::response([
        'identificationField' => '12345.67890',
        'nossoNumero' => '999',
        'barCode' => '12345678901234567890123456789012345678901234',
    ], 200)]);

    $result = paymentResource()->identificationField('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(IdentificationFieldDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/identificationField');
});

// --- all (lazy iterator) ---

it('iterates all payments lazily', function (array $page1): void {
    $page2 = [
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 50,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'pay_3', 'status' => 'PAID']],
    ];

    Http::fakeSequence()
        ->push($page1, 200)
        ->push($page2, 200);

    $items = iterator_to_array(paymentResource()->all(['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeInstanceOf(PaymentDTO::class);
    expect($items[2]->id)->toBe('pay_3');
})->with('payment_list_fixture');

// --- captureAuthorized ---

it('captures an authorized payment', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->captureAuthorized('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/captureAuthorizedPayment'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- payWithCreditCard ---

it('pays with credit card', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->payWithCreditCard('pay_abc123', ['creditCardToken' => 'tok_123']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/payWithCreditCard'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- receiveInCash ---

it('marks payment as received in cash', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->receiveInCash('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/receiveInCash'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- undoReceivedInCash ---

it('undoes received in cash', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->undoReceivedInCash('pay_abc123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/undoReceivedInCash'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- billingInfo ---

it('gets billing info for payment', function (): void {
    Http::fake(['*' => Http::response([
        'bankSlipUrl' => 'https://example.com/slip',
        'invoiceUrl' => 'https://example.com/invoice',
    ], 200)]);

    $result = paymentResource()->billingInfo('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(BillingInfoDTO::class);
    expect($result->data->bankSlipUrl)->toBe('https://example.com/slip');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/billingInfo');
});

// --- viewingInfo ---

it('gets viewing info for payment', function (): void {
    Http::fake(['*' => Http::response([
        'viewed' => true,
        'viewedDate' => '2026-03-25',
    ], 200)]);

    $result = paymentResource()->viewingInfo('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(ViewingInfoDTO::class);
    expect($result->data->viewed)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/viewingInfo');
});

// --- simulate ---

it('simulates a payment', function (): void {
    Http::fake(['*' => Http::response([
        'value' => 100.00,
        'netValue' => 96.51,
    ], 200)]);

    $result = paymentResource()->simulate(['value' => 100.00, 'billingType' => 'PIX']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentSimulationDTO::class);
    expect($result->data->netValue)->toBe(96.51);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/simulate'
        && $request->method() === 'POST');
});

// --- limits ---

it('gets payment limits', function (): void {
    Http::fake(['*' => Http::response([
        'creation' => ['daily' => 50000],
        'transfer' => ['daily' => 10000],
    ], 200)]);

    $result = paymentResource()->limits();

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PaymentLimitsDTO::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/limits');
});

// --- error handling ---

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = paymentResource()->create([
        'customer' => 'cus_456',
        'billingType' => 'PIX',
        'value' => 0,
        'dueDate' => '2026-04-01',
    ]);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
