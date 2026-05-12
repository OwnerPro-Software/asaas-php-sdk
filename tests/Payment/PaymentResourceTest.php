<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest;
use OwnerPro\Asaas\Payment\Request\ReceivePaymentInCashRequest;
use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Payment\Request\SimulatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\Environment;

mutates(PaymentResource::class);

function paymentConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
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
    expect($result->data)->toBeArray();
    expect($result->data['id'])->toBe('pay_abc123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
})->with('payment_fixture');

it('creates a payment from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->create(new CreatePaymentRequest(
        customer: 'cus_456',
        billingType: 'PIX',
        value: 150.00,
        dueDate: '2026-04-01',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_abc123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
        && $request->method() === 'POST');
})->with('payment_fixture');

it('validates required fields when creating from array', function (): void {
    paymentResource()->create(['customer' => 'cus_456']);
})->throws(InvalidArgumentException::class);

// --- find ---

it('finds a payment by id', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->find('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_abc123');
    expect($result->data['billingType'])->toBe('PIX');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123');
})->with('payment_fixture');

// --- list ---

it('lists payments with pagination', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->list(['limit' => 10]);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0])->toBeArray();
    expect($result->totalCount)->toBe(50);
    expect($result->hasMore)->toBeTrue();

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/payments'));
})->with('payment_list_fixture');

// --- update ---

it('updates a payment from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->update('pay_abc123', ['value' => 200.00]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123'
        && $request->method() === 'PUT');
})->with('payment_fixture');

it('updates a payment from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->update('pay_abc123', new UpdatePaymentRequest(value: 200.00));

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
    expect($result->data)->toBeArray();

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
    expect($result->data)->toBeArray();
    expect($result->data['status'])->toBe('CONFIRMED');

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
    expect($result->data)->toBeArray();
    expect($result->data['payload'])->toBe('00020126...');

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
    expect($result->data)->toBeArray();

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
    expect($items[0])->toBeArray();
    expect($items[2]['id'])->toBe('pay_3');
})->with('payment_list_fixture');

// --- captureAuthorized ---

it('captures an authorized payment', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->captureAuthorized('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/captureAuthorizedPayment'
        && $request->method() === 'POST');
})->with('payment_fixture');

// --- payWithCreditCard ---

it('pays with credit card', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->payWithCreditCard('pay_abc123', [
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
        'remoteIp' => '203.0.113.42',
    ]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/payWithCreditCard'
        && $request->method() === 'POST'
        && $request->data()['remoteIp'] === '203.0.113.42');
})->with('payment_fixture');

// --- receiveInCash ---

it('marks payment as received in cash', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->receiveInCash('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

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
    expect($result->data)->toBeArray();
    expect($result->data['bankSlipUrl'])->toBe('https://example.com/slip');

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
    expect($result->data)->toBeArray();
    expect($result->data['viewed'])->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/viewingInfo');
});

// --- simulate ---

it('simulates a payment', function (): void {
    Http::fake(['*' => Http::response([
        'value' => 100.00,
        'netValue' => 96.51,
    ], 200)]);

    $result = paymentResource()->simulate(['value' => 100.00, 'billingTypes' => ['PIX']]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();
    expect($result->data['netValue'])->toBe(96.51);

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
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/limits');
});

// --- typed DTOs ---

it('creates a payment with typed split and callback DTOs', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->create(new CreatePaymentRequest(
        customer: 'cus_456',
        billingType: 'CREDIT_CARD',
        value: 150.00,
        dueDate: '2026-04-01',
        split: [new Split(walletId: 'wal_1', fixedValue: 10.00)],
        callback: new Callback(successUrl: 'https://example.com'),
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['split'] === [['walletId' => 'wal_1', 'fixedValue' => 10.00]]
            && $body['callback'] === ['successUrl' => 'https://example.com']
            && $body['creditCard'] === ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']
            && $body['creditCardHolderInfo'] === ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'];
    });
})->with('payment_fixture');

it('refunds a payment from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->refund('pay_abc123', new RefundPaymentRequest(
        value: 50.00,
        description: 'Partial refund',
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/refund'
        && $request->method() === 'POST'
        && $request->data()['value'] === 50.00);
})->with('payment_fixture');

it('pays with credit card from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->payWithCreditCard('pay_abc123', new PayWithCreditCardRequest(
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '203.0.113.42',
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/payWithCreditCard'
        && $request->method() === 'POST'
        && $request->data()['creditCard']['holderName'] === 'John'
        && $request->data()['remoteIp'] === '203.0.113.42');
})->with('payment_fixture');

it('receives in cash from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->receiveInCash('pay_abc123', new ReceivePaymentInCashRequest(
        paymentDate: '2026-03-26',
        value: 100.00,
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/receiveInCash'
        && $request->method() === 'POST'
        && $request->data()['paymentDate'] === '2026-03-26');
})->with('payment_fixture');

it('updates a payment with typed split DTOs', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = paymentResource()->update('pay_abc123', new UpdatePaymentRequest(
        value: 200.00,
        split: [new Split(walletId: 'wal_1', fixedValue: 15.00)],
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['split'] === [['walletId' => 'wal_1', 'fixedValue' => 15.00]];
    });
})->with('payment_fixture');

it('simulates a payment from request object', function (): void {
    Http::fake(['*' => Http::response([
        'value' => 100.00,
        'netValue' => 96.51,
    ], 200)]);

    $result = paymentResource()->simulate(new SimulatePaymentRequest(
        value: 100.00,
        billingTypes: ['PIX'],
    ));

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/simulate'
        && $request->method() === 'POST'
        && $request->data()['billingTypes'] === ['PIX']);
});

// --- 1.5.0 new endpoints ---

it('creates a payment with credit card via trailing-slash endpoint', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->createWithCreditCard([
        'customer' => 'cus_456',
        'billingType' => 'CREDIT_CARD',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
        'creditCardToken' => 'tok_xyz',
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/'
        && $request->method() === 'POST');
})->with('payment_fixture');

it('lists refunds for a payment', function (): void {
    Http::fake(['*' => Http::response(['object' => 'list', 'data' => [['id' => 'ref_1']]], 200)]);

    $result = paymentResource()->listRefunds('pay_abc123');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/refunds'
        && $request->method() === 'GET');
});

it('refunds a bank slip payment', function (): void {
    Http::fake(['*' => Http::response(['requestUrl' => 'https://sandbox.asaas.com/solicitar-estorno/xyz'], 200)]);

    $result = paymentResource()->refundBankSlip('pay_abc123');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/bankSlip/refund'
        && $request->method() === 'POST');
});

it('gets chargeback details for a payment', function (): void {
    Http::fake(['*' => Http::response(['status' => 'REQUESTED'], 200)]);

    $result = paymentResource()->getChargeback('pay_abc123');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/chargeback'
        && $request->method() === 'GET');
});

it('gets escrow details for a payment', function (): void {
    Http::fake(['*' => Http::response(['status' => 'AWAITING_RELEASE'], 200)]);

    $result = paymentResource()->getEscrow('pay_abc123');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/escrow'
        && $request->method() === 'GET');
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
    expect($result->response->status())->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
