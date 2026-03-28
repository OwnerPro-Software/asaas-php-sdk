<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\HasArrayFactory;

mutates(HasArrayFactory::class);

final class FactoryTestRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name', 'email'];
    }
}

final class FactoryTestMidDefaultRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $name,
        public readonly ?string $middle = null,
        public readonly string $email = 'default@test.com',
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name'];
    }
}

final class FactoryTestNoConstructorRequest
{
    use HasArrayFactory;

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
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
})->throws(InvalidArgumentException::class, "Field 'email' is required.");

it('converts to array without null values', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->toArray())->toBe(['name' => 'John', 'email' => 'j@t.com']);
});

it('keeps falsy values in toArray', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => '', 'email' => 'j@t.com', 'phone' => '']);

    expect($request->toArray())->toBe(['name' => '', 'email' => 'j@t.com', 'phone' => '']);
});

it('throws when class has no constructor', function (): void {
    FactoryTestNoConstructorRequest::fromArray(['name' => 'John']);
})->throws(InvalidArgumentException::class, 'must have a constructor');

it('includes default values for skipped mid-constructor params', function (): void {
    // Only provide 'name' and 'email' but not 'middle'
    // The elseif branch must include the default for 'middle' so positional args are correct
    $request = FactoryTestMidDefaultRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->name)->toBe('John');
    expect($request->middle)->toBeNull();
    expect($request->email)->toBe('j@t.com');
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
})->throws(InvalidArgumentException::class, "Field 'email' is required.");

it('strips null values when resolving from array', function (): void {
    $result = FactoryTestRequest::resolveData(['name' => 'John', 'email' => 'j@t.com']);

    expect($result)->not->toHaveKey('phone');
});

// --- Recursive toArray() regression tests (one per previously-overriding class) ---

it('CreatePaymentRequest: serializes nested Split DTOs, Callback, CreditCard, CreditCardHolderInfo', function (): void {
    $request = new OwnerPro\Asaas\Payment\Request\CreatePaymentRequest(
        customer: 'cus_1',
        billingType: 'CREDIT_CARD',
        value: 100.00,
        dueDate: '2026-01-01',
        split: [new OwnerPro\Asaas\Support\DTO\Split(walletId: 'wal_1', fixedValue: 10.00)],
        callback: new OwnerPro\Asaas\Support\DTO\Callback(successUrl: 'https://ok.com'),
        creditCard: new OwnerPro\Asaas\Support\DTO\CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
    );

    $array = $request->toArray();

    expect($array['split'])->toBe([['walletId' => 'wal_1', 'fixedValue' => 10.00]]);
    expect($array['callback'])->toBe(['successUrl' => 'https://ok.com']);
    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
    expect($array['creditCardHolderInfo'])->toBe(['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999']);
});

it('CreatePaymentRequest: passes raw arrays through as-is', function (): void {
    $request = new OwnerPro\Asaas\Payment\Request\CreatePaymentRequest(
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
    $request = new OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest(
        value: 200.00,
        split: [new OwnerPro\Asaas\Support\DTO\Split(walletId: 'wal_1', fixedValue: 20.00)],
    );

    expect($request->toArray())->toBe([
        'value' => 200.00,
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 20.00]],
    ]);
});

it('CreateInvoiceRequest: serializes nested Taxes DTO', function (): void {
    $request = new OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest(
        serviceDescription: 'Service',
        observations: 'Obs',
        value: 500.00,
        deductions: 0.0,
        effectiveDate: '2026-01-01',
        municipalServiceName: 'Consulting',
        taxes: new OwnerPro\Asaas\Support\DTO\Taxes(retainIss: true, iss: 5.0, pis: 0.65, cofins: 3.0, csll: 1.0, inss: 0.0, ir: 1.5),
    );

    $array = $request->toArray();

    expect($array['taxes'])->toBe(['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
});

it('CreateInvoiceRequest: passes raw array taxes through as-is', function (): void {
    $request = new OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest(
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
    $request = new OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest(
        value: 600.00,
        taxes: new OwnerPro\Asaas\Support\DTO\Taxes(retainIss: false, iss: 3.0, pis: 0.65, cofins: 3.0, csll: 1.0, inss: 0.0, ir: 1.5),
    );

    $array = $request->toArray();

    expect($array['taxes'])->toBe(['retainIss' => false, 'iss' => 3.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 0.0, 'ir' => 1.5]);
    expect($array['value'])->toBe(600.00);
});

it('TransferRequest: serializes nested BankAccount DTO', function (): void {
    $request = new OwnerPro\Asaas\Transfer\Request\TransferRequest(
        value: 1000.00,
        bankAccount: new OwnerPro\Asaas\Support\DTO\BankAccount(
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
    $request = new OwnerPro\Asaas\Transfer\Request\TransferRequest(
        value: 1000.00,
        bankAccount: ['ownerName' => 'John', 'cpfCnpj' => '12345678901', 'agency' => '1234', 'account' => '56789', 'accountDigit' => '0'],
    );

    $array = $request->toArray();

    expect($array['bankAccount'])->toBe(['ownerName' => 'John', 'cpfCnpj' => '12345678901', 'agency' => '1234', 'account' => '56789', 'accountDigit' => '0']);
});

it('CreditCardRequest: serializes nested CreditCard and CreditCardHolderInfo DTOs', function (): void {
    $request = new OwnerPro\Asaas\CreditCard\Request\CreditCardRequest(
        customer: 'cus_1',
        creditCard: new OwnerPro\Asaas\Support\DTO\CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '127.0.0.1',
    );

    $array = $request->toArray();

    expect($array['creditCard'])->toBe(['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123']);
    expect($array['creditCardHolderInfo'])->toBe(['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '11999']);
});

it('BankAccount: serializes nested Bank DTO via recursive toArray', function (): void {
    $account = new OwnerPro\Asaas\Support\DTO\BankAccount(
        ownerName: 'John',
        cpfCnpj: '12345678901',
        agency: '1234',
        account: '56789',
        accountDigit: '0',
        bank: new OwnerPro\Asaas\Support\DTO\Bank(code: '001'),
    );

    expect($account->toArray())->toBe([
        'ownerName' => 'John',
        'cpfCnpj' => '12345678901',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'bank' => ['code' => '001'],
    ]);
});

it('PayQrCodeRequest: serializes nested QrCodePayload DTO via recursive toArray', function (): void {
    $request = new OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest(
        qrCode: new OwnerPro\Asaas\Support\DTO\QrCodePayload(payload: '00020126...'),
        value: 100.00,
    );

    expect($request->toArray())->toBe([
        'qrCode' => ['payload' => '00020126...'],
        'value' => 100.00,
    ]);
});
