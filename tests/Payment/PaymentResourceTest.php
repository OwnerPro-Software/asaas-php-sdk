<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Payment\PaymentDocumentType;
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
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\DTO\Interest;
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

it('serializes float discount/interest/fine as object shape on the wire when creating', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->create([
        'customer' => 'cus_456',
        'billingType' => 'BOLETO',
        'value' => 100.00,
        'dueDate' => '2026-04-01',
        'discount' => 5.0,
        'interest' => 1.5,
        'fine' => 2.0,
    ]);

    Http::assertSent(function ($request): bool {
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/payments' || $request->method() !== 'POST') {
            return false;
        }
        $body = $request->data();

        return $body['discount'] === ['value' => 5.0]
            && $body['interest'] === ['value' => 1.5]
            && $body['fine'] === ['value' => 2.0];
    });
})->with('payment_fixture');

it('serializes typed Discount/Interest/Fine DTOs on the wire when creating', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->create(new CreatePaymentRequest(
        customer: 'cus_456',
        billingType: 'BOLETO',
        value: 100.00,
        dueDate: '2026-04-01',
        discount: new Discount(value: 10.0, dueDateLimitDays: 3, type: DiscountType::Percentage),
        interest: new Interest(value: 1.5),
        fine: new Fine(value: 2.0, type: FineType::Fixed),
    ));

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['discount'] === ['value' => 10.0, 'dueDateLimitDays' => 3, 'type' => 'PERCENTAGE']
            && $body['interest'] === ['value' => 1.5]
            && $body['fine'] === ['value' => 2.0, 'type' => 'FIXED'];
    });
})->with('payment_fixture');

it('serializes float discount/interest/fine as object shape on the wire when updating', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->update('pay_abc123', [
        'discount' => 5.0,
        'interest' => 1.5,
        'fine' => 2.0,
    ]);

    Http::assertSent(function ($request): bool {
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/payments/pay_abc123' || $request->method() !== 'PUT') {
            return false;
        }
        $body = $request->data();

        return $body['discount'] === ['value' => 5.0]
            && $body['interest'] === ['value' => 1.5]
            && $body['fine'] === ['value' => 2.0];
    });
})->with('payment_fixture');

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

it('UpdatePaymentRequest: omitted fields never reach the wire as null', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->update('pay_abc123', new UpdatePaymentRequest(value: 200.00));

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body === ['value' => 200.00]
            && ! array_key_exists('description', $body)
            && ! array_key_exists('externalReference', $body)
            && ! array_key_exists('discount', $body)
            && ! array_key_exists('callback', $body);
    });
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
        // Describes the set this two-page sequence serves, not the 50 of the
        // first page's fixture: a terminal page reporting a count it never
        // delivered is the `PAGINATION_SHORT` fault, not a complete walk.
        'totalCount' => 3,
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
        'remoteIp' => '203.0.113.42',
        'creditCardToken' => 'tok_xyz',
    ]);

    Http::assertSent(function ($request): bool {
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/payments/' || $request->method() !== 'POST') {
            return false;
        }
        $body = $request->data();

        return ($body['creditCardToken'] ?? null) === 'tok_xyz'
            && ($body['remoteIp'] ?? null) === '203.0.113.42';
    });
})->with('payment_fixture');

it('rejects createWithCreditCard when remoteIp is missing', function (): void {
    paymentResource()->createWithCreditCard([
        'customer' => 'cus_456',
        'billingType' => 'CREDIT_CARD',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
        'creditCardToken' => 'tok_xyz',
    ]);
})->throws(InvalidArgumentException::class, 'remoteIp is required');

it('rejects createWithCreditCard when neither token nor card+holder are provided', function (): void {
    paymentResource()->createWithCreditCard([
        'customer' => 'cus_456',
        'billingType' => 'CREDIT_CARD',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
        'remoteIp' => '203.0.113.42',
    ]);
})->throws(InvalidArgumentException::class, 'provide either creditCardToken');

it('rejects createWithCreditCard with card but no holder info', function (): void {
    paymentResource()->createWithCreditCard([
        'customer' => 'cus_456',
        'billingType' => 'CREDIT_CARD',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
        'remoteIp' => '203.0.113.42',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
    ]);
})->throws(InvalidArgumentException::class, 'provide either creditCardToken');

it('rejects createWithCreditCard with holder info but no card', function (): void {
    paymentResource()->createWithCreditCard([
        'customer' => 'cus_456',
        'billingType' => 'CREDIT_CARD',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
        'remoteIp' => '203.0.113.42',
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999'],
    ]);
})->throws(InvalidArgumentException::class, 'provide either creditCardToken');

it('accepts createWithCreditCard with creditCard plus holderInfo (no token)', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->createWithCreditCard(new CreatePaymentRequest(
        customer: 'cus_456',
        billingType: 'CREDIT_CARD',
        value: 150.00,
        dueDate: '2026-04-01',
        remoteIp: '203.0.113.42',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
    ));

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/'
        && $request->method() === 'POST'
        && ($request->data()['creditCardToken'] ?? null) === null
        && ($request->data()['creditCard']['holderName'] ?? null) === 'John'
        && ($request->data()['creditCardHolderInfo']['email'] ?? null) === 'j@t.com'
        && ($request->data()['remoteIp'] ?? null) === '203.0.113.42');
})->with('payment_fixture');

it('pins creditCardToken on the wire when passed via DTO', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    paymentResource()->createWithCreditCard(new CreatePaymentRequest(
        customer: 'cus_456',
        billingType: 'CREDIT_CARD',
        value: 150.00,
        dueDate: '2026-04-01',
        remoteIp: '203.0.113.42',
        creditCardToken: 'tok_xyz',
    ));

    Http::assertSent(fn ($request): bool => ($request->data()['creditCardToken'] ?? null) === 'tok_xyz'
        && ($request->data()['remoteIp'] ?? null) === '203.0.113.42');
})->with('payment_fixture');

it('lists refunds for a payment as a paginated result', function (): void {
    // The endpoint answers with the standard envelope, so a payment with more
    // refunds than the page limit needs next()/hasMore like every other list.
    Http::fake(['*' => Http::response([
        'object' => 'list',
        'hasMore' => true,
        'totalCount' => 12,
        'limit' => 10,
        'offset' => 0,
        'data' => [['id' => 'ref_1']],
    ], 200)]);

    $result = paymentResource()->listRefunds('pay_abc123', ['limit' => 10]);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->hasMore)->toBeTrue();
    expect($result->totalCount)->toBe(12);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/refunds?limit=10'
        && $request->method() === 'GET');
});

it('walks every refund page through allRefunds()', function (): void {
    Http::fakeSequence()
        ->push(['object' => 'list', 'hasMore' => true, 'totalCount' => 2, 'limit' => 1, 'offset' => 0, 'data' => [['id' => 'ref_1']]])
        ->push(['object' => 'list', 'hasMore' => false, 'totalCount' => 2, 'limit' => 1, 'offset' => 1, 'data' => [['id' => 'ref_2']]]);

    $refunds = iterator_to_array(paymentResource()->allRefunds('pay_abc123', ['limit' => 1]), preserve_keys: false);

    expect($refunds)->toBe([['id' => 'ref_1'], ['id' => 'ref_2']]);
});

it('forwards the query to listDocuments()', function (): void {
    Http::fake(['*' => Http::response(['object' => 'list', 'data' => []], 200)]);

    paymentResource()->listDocuments('pay_abc123', ['limit' => 5]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/documents?limit=5');
});

it('refunds a bank slip payment without a body', function (): void {
    Http::fake(['*' => Http::response(['requestUrl' => 'https://sandbox.asaas.com/solicitar-estorno/xyz'], 200)]);

    $result = paymentResource()->refundBankSlip('pay_abc123');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/bankSlip/refund'
        && $request->method() === 'POST'
        && $request->data() === []);
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

it('finishes escrow by the guarantee id returned by getEscrow, not the payment id', function (): void {
    Http::fake(['*' => Http::response(['object' => 'payment', 'id' => 'pay_abc123'], 200)]);

    $result = paymentResource()->finishEscrow('4f468235-cec3-482f-b3d0-348af4c7194');

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/escrow/4f468235-cec3-482f-b3d0-348af4c7194/finish'
        && $request->method() === 'POST');
});

it('rejects empty id on finishEscrow', function (): void {
    paymentResource()->finishEscrow('');
})->throws(InvalidArgumentException::class);

// --- splits ---

it('lists splits paid via paginate', function (): void {
    Http::fake(['*' => Http::response(['data' => [['id' => 'sp_1']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0], 200)]);

    $result = paymentResource()->listSplitsPaid(['walletId' => 'wal_1']);

    expect($result->success)->toBeTrue();
    expect($result->data[0]['id'])->toBe('sp_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/payments/splits/paid')
        && str_contains($r->url(), 'walletId=wal_1'));
});

it('finds a single split paid', function (): void {
    Http::fake(['*' => Http::response(['id' => 'sp_1'], 200)]);

    paymentResource()->findSplitPaid('sp_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/payments/splits/paid/sp_1');
});

it('rejects empty id on findSplitPaid', function (): void {
    paymentResource()->findSplitPaid('');
})->throws(InvalidArgumentException::class);

it('lists splits received via paginate', function (): void {
    Http::fake(['*' => Http::response(['data' => [['id' => 'sr_1']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0], 200)]);

    paymentResource()->listSplitsReceived();

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/payments/splits/received'));
});

it('finds a single split received', function (): void {
    Http::fake(['*' => Http::response(['id' => 'sr_1'], 200)]);

    paymentResource()->findSplitReceived('sr_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/payments/splits/received/sr_1');
});

it('rejects empty id on findSplitReceived', function (): void {
    paymentResource()->findSplitReceived('');
})->throws(InvalidArgumentException::class);

// --- documents ---

it('uploads a payment document via multipart', function (): void {
    Http::fake(['*' => Http::response(['id' => 'doc_1', 'type' => 'INVOICE'], 200)]);

    $result = paymentResource()->uploadDocument(
        paymentId: 'pay_abc123',
        file: 'binary-bytes',
        type: PaymentDocumentType::Invoice,
        availableAfterPayment: true,
        filename: 'nf.pdf',
    );

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('doc_1');

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/documents') {
            return false;
        }
        if (! str_contains((string) $request->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }
        $body = (string) $request->body();

        return str_contains($body, 'INVOICE')
            && str_contains($body, "name=\"availableAfterPayment\"\r\nContent-Length: 4\r\n\r\ntrue")
            && str_contains($body, 'filename="nf.pdf"');
    });
});

it('accepts a string document type on upload', function (): void {
    Http::fake(['*' => Http::response(['id' => 'doc_2'], 200)]);

    paymentResource()->uploadDocument(
        paymentId: 'pay_abc123',
        file: 'x',
        type: 'NEW_CUSTOM_TYPE',
        availableAfterPayment: false,
        filename: 'x.pdf',
    );

    Http::assertSent(fn ($r): bool => str_contains((string) $r->body(), 'NEW_CUSTOM_TYPE')
        && str_contains((string) $r->body(), "name=\"availableAfterPayment\"\r\nContent-Length: 5\r\n\r\nfalse"));
});

it('rejects empty paymentId on uploadDocument', function (): void {
    paymentResource()->uploadDocument(
        paymentId: '',
        file: 'x',
        type: 'INVOICE',
        availableAfterPayment: true,
        filename: 'x.pdf',
    );
})->throws(InvalidArgumentException::class);

it('lists payment documents via paginate', function (): void {
    Http::fake(['*' => Http::response(['data' => [['id' => 'doc_1']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0], 200)]);

    $result = paymentResource()->listDocuments('pay_abc123');

    expect($result->success)->toBeTrue();
    expect($result->data[0]['id'])->toBe('doc_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/documents'));
});

it('finds a payment document', function (): void {
    Http::fake(['*' => Http::response(['id' => 'doc_1', 'type' => 'INVOICE'], 200)]);

    $result = paymentResource()->findDocument('pay_abc123', 'doc_1');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/documents/doc_1');
});

it('updates a payment document', function (): void {
    Http::fake(['*' => Http::response(['id' => 'doc_1', 'type' => 'CONTRACT'], 200)]);

    $result = paymentResource()->updateDocument('pay_abc123', 'doc_1', [
        'availableAfterPayment' => false,
        'type' => PaymentDocumentType::Contract,
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($r): bool {
        if ($r->method() !== 'PUT') {
            return false;
        }
        $body = $r->data();

        return ($body['availableAfterPayment'] ?? null) === false && ($body['type'] ?? null) === 'CONTRACT';
    });
});

it('deletes a payment document', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true], 200)]);

    $result = paymentResource()->deleteDocument('pay_abc123', 'doc_1');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($r): bool => $r->method() === 'DELETE'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/payments/pay_abc123/documents/doc_1');
});

it('rejects empty documentId on findDocument', function (): void {
    paymentResource()->findDocument('pay_abc123', '');
})->throws(InvalidArgumentException::class);

it('rejects empty paymentId on findDocument', function (): void {
    paymentResource()->findDocument('', 'doc_1');
})->throws(InvalidArgumentException::class);

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

it('ships a gap-carrying split list as a JSON array on the wire', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $splits = [
        ['walletId' => 'wallet_drop', 'fixedValue' => 1.00],
        ['walletId' => 'wallet_keep', 'fixedValue' => 2.00],
    ];

    paymentResource()->create([
        'customer' => 'cus_456',
        'billingType' => 'PIX',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
        'split' => array_filter($splits, fn (array $split): bool => $split['walletId'] !== 'wallet_drop'),
    ]);

    Http::assertSent(function ($request): bool {
        expect($request->body())->toContain('"split":[{"walletId":"wallet_keep"');

        return $request->url() === 'https://api-sandbox.asaas.com/v3/payments';
    });
})->with('payment_fixture');
