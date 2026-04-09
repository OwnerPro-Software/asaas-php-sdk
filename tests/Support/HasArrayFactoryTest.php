<?php

declare(strict_types=1);

use OwnerPro\Asaas\CreditCard\Request\CreditCardRequest;
use OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest;
use OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest;
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;
use OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\DTO\Bank;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\QrCodePayload;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\DTO\SplitRefund;
use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Transfer\Request\TransferRequest;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use OwnerPro\Asaas\Webhook\WebhookEvent;

mutates(HasArrayFactory::class);

final class FactoryTestRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
        );
    }
}

it('creates from array with required fields and applies defaults', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->name)->toBe('John');
    expect($request->email)->toBe('j@t.com');
    expect($request->phone)->toBeNull();
    expect(isset($request->phone))->toBeFalse();
});

it('creates from array with optional fields', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com', 'phone' => '123']);

    expect($request->phone)->toBe('123');
});

it('ignores extra keys not in constructor', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com', 'unknown' => 'x']);

    expect($request->name)->toBe('John');
});

it('throws when required field is missing', function (): void {
    FactoryTestRequest::fromArray(['name' => 'John']);
})->throws(TypeError::class);

it('converts to array without null values', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->toArray())->toBe(['name' => 'John', 'email' => 'j@t.com']);
});

it('keeps falsy values in toArray', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => '', 'email' => 'j@t.com', 'phone' => '']);

    expect($request->toArray())->toBe(['name' => '', 'email' => 'j@t.com', 'phone' => '']);
});

// --- resolveData ---

it('resolves an array into a validated array via resolveData', function (): void {
    $result = FactoryTestRequest::resolveData(['name' => 'John', 'email' => 'j@t.com', 'phone' => '123']);

    expect($result)->toBe(['name' => 'John', 'email' => 'j@t.com', 'phone' => '123']);
});

it('resolves a request object into an array via resolveData', function (): void {
    $request = new FactoryTestRequest(name: 'John', email: 'j@t.com');

    $result = FactoryTestRequest::resolveData($request);

    expect($result)->toBe(['name' => 'John', 'email' => 'j@t.com']);
});

it('validates required fields when resolving from array', function (): void {
    FactoryTestRequest::resolveData(['name' => 'John']);
})->throws(TypeError::class);

it('strips null values when resolving from array', function (): void {
    $result = FactoryTestRequest::resolveData(['name' => 'John', 'email' => 'j@t.com']);

    expect($result)->not->toHaveKey('phone');
});

// --- Recursive toArray() regression tests (one per previously-overriding class) ---

it('CreatePaymentRequest: serializes nested Split DTOs, Callback, CreditCard, CreditCardHolderInfo', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_1',
        billingType: 'CREDIT_CARD',
        value: 100.00,
        dueDate: '2026-01-01',
        split: [new Split(walletId: 'wal_1', fixedValue: 10.00)],
        callback: new Callback(successUrl: 'https://ok.com'),
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
    );

    $array = $request->toArray();

    expect($array['split'])->toBe([['walletId' => 'wal_1', 'fixedValue' => 10.00]]);
    expect($array['callback'])->toBe(['successUrl' => 'https://ok.com']);
    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
    expect($array['creditCardHolderInfo'])->toBe(['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999']);
});

it('CreatePaymentRequest: passes raw arrays through as-is', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_1',
        billingType: 'PIX',
        value: 100.00,
        dueDate: '2026-01-01',
        split: [['walletId' => 'wal_1', 'fixedValue' => 10.00]],
        callback: ['successUrl' => 'https://ok.com'],
        creditCard: ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
    );

    $array = $request->toArray();

    expect($array['split'])->toBe([['walletId' => 'wal_1', 'fixedValue' => 10.00]]);
    expect($array['callback'])->toBe(['successUrl' => 'https://ok.com']);
    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
});

it('UpdatePaymentRequest: serializes nested Split DTOs', function (): void {
    $request = new UpdatePaymentRequest(
        value: 200.00,
        split: [new Split(walletId: 'wal_1', fixedValue: 20.00)],
    );

    expect($request->toArray())->toBe([
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 20.00]],
        'value' => 200.00,
    ]);
});

it('CreateInvoiceRequest: serializes nested Taxes DTO', function (): void {
    $request = new CreateInvoiceRequest(
        serviceDescription: 'Service',
        observations: 'Obs',
        value: 500.00,
        deductions: 0.0,
        effectiveDate: '2026-01-01',
        municipalServiceName: 'Consulting',
        taxes: new Taxes(retainIss: true, iss: 5.0, pis: 0.65, cofins: 3.0, csll: 1.0, inss: 0.0, ir: 1.5),
    );

    $array = $request->toArray();

    expect($array['taxes'])->toBe(['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
});

it('CreateInvoiceRequest: passes raw array taxes through as-is', function (): void {
    $request = new CreateInvoiceRequest(
        serviceDescription: 'Service',
        observations: 'Obs',
        value: 500.00,
        deductions: 0.0,
        effectiveDate: '2026-01-01',
        municipalServiceName: 'Consulting',
        taxes: ['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5],
    );

    $array = $request->toArray();

    expect($array['taxes'])->toBe(['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
});

it('UpdateInvoiceRequest: serializes nested Taxes DTO', function (): void {
    $request = new UpdateInvoiceRequest(
        value: 600.00,
        taxes: new Taxes(retainIss: false, iss: 3.0, pis: 0.65, cofins: 3.0, csll: 1.0, inss: 0.0, ir: 1.5),
    );

    $array = $request->toArray();

    expect($array['taxes'])->toBe(['retainIss' => false, 'iss' => 3.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
    expect($array['value'])->toBe(600.00);
});

it('TransferRequest: serializes nested BankAccount DTO', function (): void {
    $request = new TransferRequest(
        value: 1000.00,
        bankAccount: new BankAccount(
            ownerName: 'John',
            cpfCnpj: '12345678901',
            agency: '1234',
            account: '56789',
            accountDigit: '0',
        ),
    );

    $array = $request->toArray();

    expect($array['bankAccount'])->toBe([
        'ownerName' => 'John',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
    ]);
});

it('TransferRequest: passes raw array bankAccount through as-is', function (): void {
    $request = new TransferRequest(
        value: 1000.00,
        bankAccount: ['ownerName' => 'John', 'cpfCnpj' => '12345678901', 'agency' => '1234', 'account' => '56789', 'accountDigit' => '0'],
    );

    $array = $request->toArray();

    expect($array['bankAccount'])->toBe(['ownerName' => 'John', 'cpfCnpj' => '12345678901', 'agency' => '1234', 'account' => '56789', 'accountDigit' => '0']);
});

it('CreditCardRequest: serializes nested CreditCard and CreditCardHolderInfo DTOs', function (): void {
    $request = new CreditCardRequest(
        customer: 'cus_1',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '127.0.0.1',
    );

    $array = $request->toArray();

    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
    expect($array['creditCardHolderInfo'])->toBe(['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999']);
});

it('BankAccount: serializes nested Bank DTO via recursive toArray', function (): void {
    $account = new BankAccount(
        ownerName: 'John',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
        bank: new Bank(code: '001'),
    );

    expect($account->toArray())->toBe([
        'bank' => ['code' => '001'],
        'ownerName' => 'John',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
    ]);
});

it('PayQrCodeRequest: serializes nested QrCodePayload DTO via recursive toArray', function (): void {
    $request = new PayQrCodeRequest(
        qrCode: new QrCodePayload(payload: '00020126...'),
        value: 100.00,
    );

    expect($request->toArray())->toBe([
        'qrCode' => ['payload' => '00020126...'],
        'value' => 100.00,
    ]);
});

it('serializes BackedEnum properties to their string value in toArray', function (): void {
    $request = new CreatePaymentRequest(
        customer: 'cus_1',
        billingType: BillingType::Pix,
        value: 100.00,
        dueDate: '2026-01-01',
    );

    $array = $request->toArray();

    expect($array['billingType'])->toBe('PIX');
});

it('serializes BackedEnum items in arrays to their string value in toArray', function (): void {
    $request = CreateWebhookRequest::fromArray([
        'url' => 'https://example.com',
        'email' => 'test@test.com',
        'events' => [WebhookEvent::PaymentCreated, WebhookEvent::PaymentReceived],
    ]);

    $array = $request->toArray();

    expect($array['events'])->toBe(['PAYMENT_CREATED', 'PAYMENT_RECEIVED']);
});

it('fromArray hydrates nested array into DTO when union type includes a DTO class', function (): void {
    $request = CreatePaymentRequest::fromArray([
        'customer' => 'cus_1',
        'billingType' => 'PIX',
        'value' => 100.00,
        'dueDate' => '2026-01-01',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'callback' => ['successUrl' => 'https://ok.com'],
    ]);

    expect($request->creditCard)->toBeInstanceOf(CreditCard::class);
    expect($request->creditCard->holderName)->toBe('John');
    expect($request->callback)->toBeInstanceOf(Callback::class);
    expect($request->callback->successUrl)->toBe('https://ok.com');
});

it('fromArray throws when nested array is missing required DTO fields', function (): void {
    CreatePaymentRequest::fromArray([
        'customer' => 'cus_1',
        'billingType' => 'PIX',
        'value' => 100.00,
        'dueDate' => '2026-01-01',
        'creditCard' => ['holderName' => 'John'],
    ]);
})->throws(InvalidArgumentException::class);

it('nested DTOs implement Arrayable', function (string $class): void {
    expect(new $class(...array_map(
        fn (ReflectionParameter $p): mixed => match (true) {
            $p->isDefaultValueAvailable() => $p->getDefaultValue(),
            (string) $p->getType() === 'string' => 'x',
            (string) $p->getType() === 'float' => 0.0,
            (string) $p->getType() === 'bool' => false,
            default => 'x',
        },
        (new ReflectionClass($class))->getConstructor()->getParameters(),
    )))->toBeInstanceOf(Arrayable::class);
})->with([
    Bank::class,
    BankAccount::class,
    Callback::class,
    CreditCard::class,
    CreditCardHolderInfo::class,
    QrCodePayload::class,
    Split::class,
    SplitRefund::class,
    Taxes::class,
]);

it('serializes mixed enum and string items in arrays', function (): void {
    $request = CreateWebhookRequest::fromArray([
        'url' => 'https://example.com',
        'email' => 'test@test.com',
        'events' => [WebhookEvent::PaymentCreated, 'PAYMENT_UPDATED'],
    ]);

    $array = $request->toArray();

    expect($array['events'])->toBe(['PAYMENT_CREATED', 'PAYMENT_UPDATED']);
});
