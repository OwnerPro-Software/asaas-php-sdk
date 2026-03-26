# Asaas PHP SDK

Clean PHP SDK for the [Asaas](https://www.asaas.com/) payment platform API with typed resources, tolerant responses, and result-based error handling.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation

```bash
composer require ownerpro/asaas-php-sdk
```

The package auto-discovers its ServiceProvider and Facade.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=asaas-config
```

Set the following environment variables:

```env
ASAAS_API_KEY=your-api-key
ASAAS_ENVIRONMENT=sandbox   # or "production"
ASAAS_TIMEOUT=30
```

## Usage

### Via Facade

```php
use OwnerPro\Asaas\Asaas;

$result = Asaas::payments()->create([
    'customer' => 'cus_abc123',
    'billingType' => 'PIX',
    'value' => 150.00,
    'dueDate' => '2026-04-01',
]);
```

### Via Dependency Injection

```php
use OwnerPro\Asaas\AsaasClient;

public function __construct(private AsaasClient $asaas) {}

public function charge(): void
{
    $result = $this->asaas->payments()->create([
        'customer' => 'cus_abc123',
        'billingType' => 'PIX',
        'value' => 150.00,
        'dueDate' => '2026-04-01',
    ]);
}
```

### Standalone (without Laravel)

```php
use OwnerPro\Asaas\AsaasClient;

$client = new AsaasClient(
    apiKey: 'your-api-key',
    environment: 'sandbox',
    timeout: 30,
);

$result = $client->payments()->find('pay_abc123');
```

## Result Handling

All resource methods return `AsaasResult` or `AsaasPaginatedResult`.

```php
$result = Asaas::payments()->create([...]);

if ($result->success) {
    $payment = $result->data;       // PaymentResponse
    echo $payment->id;
    echo $payment->status;
} else {
    $errors = $result->errors;      // array of error details
    $code = $result->statusCode;    // HTTP status code
}
```

### Throwing on Failure

```php
// Throws AsaasRequestException on failure, returns self on success
$result = Asaas::payments()->find('pay_abc123')->throw();
$payment = $result->data;
```

### Error Handling

```php
use OwnerPro\Asaas\Support\AsaasRequestException;

try {
    Asaas::payments()->find('pay_invalid')->throw();
} catch (AsaasRequestException $e) {
    $e->getMessage();    // First error description
    $e->statusCode;      // HTTP status code
    $e->errors;          // Full error array from API
}
```

## Input: Arrays or Request Objects

Every `create()` and `update()` method accepts either a plain array or a typed request object:

```php
// Array (validated at runtime via required fields)
$result = Asaas::payments()->create([
    'customer' => 'cus_abc123',
    'billingType' => 'PIX',
    'value' => 100.00,
    'dueDate' => '2026-04-01',
]);

// Request object (validated at construction)
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;

$result = Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123',
    billingType: 'PIX',
    value: 100.00,
    dueDate: '2026-04-01',
));
```

Request objects can also be created from arrays via `fromArray()`:

```php
$data = CreatePaymentRequest::fromArray($request->validated());
```

### Nested Value Objects

Fields like `creditCard`, `creditCardHolderInfo`, `bankAccount`, `taxes`, `split`, `callback`, and `qrCode` accept either a plain array or a typed DTO from `OwnerPro\Asaas\Support\DTO`. Using typed DTOs gives you IDE autocompletion and construction-time validation.

```php
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

// Raw array (still works)
$result = Asaas::payments()->create([
    'customer' => 'cus_abc123',
    'billingType' => 'CREDIT_CARD',
    'value' => 200.00,
    'dueDate' => '2026-04-01',
    'creditCard' => [
        'holderName' => 'John Doe',
        'number' => '4111111111111111',
        'expiryMonth' => '06',
        'expiryYear' => '2028',
        'ccv' => '123',
    ],
    'creditCardHolderInfo' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpfCnpj' => '12345678901',
        'postalCode' => '01001000',
        'addressNumber' => '100',
        'phone' => '11999999999',
    ],
]);

// Typed DTOs (IDE autocompletion + construction-time validation)
$result = Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123',
    billingType: 'CREDIT_CARD',
    value: 200.00,
    dueDate: '2026-04-01',
    creditCard: new CreditCard(
        holderName: 'John Doe',
        number: '4111111111111111',
        expiryMonth: '06',
        expiryYear: '2028',
        ccv: '123',
    ),
    creditCardHolderInfo: new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '100',
        phone: '11999999999',
    ),
));
```

Available nested DTOs (`OwnerPro\Asaas\Support\DTO\*`):

| DTO | Used in |
|-----|---------|
| `CreditCard` | `CreatePaymentRequest`, `TokenizeCreditCardRequest`, `PayWithCreditCardRequest` |
| `CreditCardHolderInfo` | `CreatePaymentRequest`, `TokenizeCreditCardRequest`, `PayWithCreditCardRequest` |
| `BankAccount` | `CreateTransferRequest` |
| `Bank` | Nested inside `BankAccount` |
| `Taxes` | `CreateInvoiceRequest`, `UpdateInvoiceRequest` |
| `Split` | `CreatePaymentRequest`, `UpdatePaymentRequest` |
| `SplitRefund` | `RefundPaymentRequest` |
| `Callback` | `CreatePaymentRequest` |
| `QrCodePayload` | `PayQrCodeRequest` |

### New Request DTOs

Beyond `create()` and `update()`, several action methods now accept typed request objects:

**Payment actions:**

```php
use OwnerPro\Asaas\Payment\Request\SimulatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest;
use OwnerPro\Asaas\Payment\Request\ReceivePaymentInCashRequest;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;
use OwnerPro\Asaas\Support\DTO\SplitRefund;

// Simulate payment
Asaas::payments()->simulate(new SimulatePaymentRequest(
    value: 500.00,
    billingTypes: ['CREDIT_CARD', 'PIX'],
    installmentCount: 3,
));

// Refund with split refunds
Asaas::payments()->refund('pay_abc123', new RefundPaymentRequest(
    value: 50.00,
    description: 'Partial refund',
    splitRefunds: [
        new SplitRefund(id: 'split_abc', value: 25.00),
        new SplitRefund(id: 'split_def', value: 25.00),
    ],
));

// Pay with credit card
Asaas::payments()->payWithCreditCard('pay_abc123', new PayWithCreditCardRequest(
    creditCard: new CreditCard(
        holderName: 'John Doe',
        number: '4111111111111111',
        expiryMonth: '06',
        expiryYear: '2028',
        ccv: '123',
    ),
    creditCardHolderInfo: new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '100',
        phone: '11999999999',
    ),
));

// Receive payment in cash
Asaas::payments()->receiveInCash('pay_abc123', new ReceivePaymentInCashRequest(
    paymentDate: '2026-03-26',
    value: 100.00,
    notifyCustomer: true,
));
```

**Pix Transactions:**

```php
use OwnerPro\Asaas\PixTransaction\Request\DecodeQrCodeRequest;
use OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest;
use OwnerPro\Asaas\Support\DTO\QrCodePayload;

// Decode a QR code
Asaas::pixTransactions()->decodeQrCode(new DecodeQrCodeRequest(
    payload: '00020126580014br.gov.bcb.pix...',
));

// Pay a QR code with typed payload
Asaas::pixTransactions()->payQrCode(new PayQrCodeRequest(
    qrCode: new QrCodePayload(
        payload: '00020126580014br.gov.bcb.pix...',
        changeValue: 5.00,
    ),
    value: 150.00,
    description: 'QR Code payment',
));
```

**Pix:**

```php
use OwnerPro\Asaas\Pix\Request\CreateStaticQrCodeRequest;

Asaas::pix()->createStaticQrCode(new CreateStaticQrCodeRequest(
    addressKey: 'abc-uuid-key',
    description: 'Store payment',
    value: 49.90,
    allowsMultiplePayments: true,
));
```

**Bill Payments:**

```php
use OwnerPro\Asaas\BillPayment\Request\SimulateBillPaymentRequest;

Asaas::billPayments()->simulate(new SimulateBillPaymentRequest(
    identificationField: '23793.38128 60000.000003 00000.000400 1 84340000012345',
));
```

### Updated Request DTOs with Nested DTO Support

`CreatePaymentRequest`, `TokenizeCreditCardRequest`, `CreateInvoiceRequest`, and `CreateTransferRequest` now accept typed nested DTOs alongside plain arrays:

```php
use OwnerPro\Asaas\CreditCard\Request\TokenizeCreditCardRequest;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

// Tokenize with typed DTOs
Asaas::creditCards()->tokenize(new TokenizeCreditCardRequest(
    customer: 'cus_abc123',
    creditCard: new CreditCard(
        holderName: 'John Doe',
        number: '4111111111111111',
        expiryMonth: '06',
        expiryYear: '2028',
        ccv: '123',
    ),
    creditCardHolderInfo: new CreditCardHolderInfo(
        name: 'John Doe',
        email: 'john@example.com',
        cpfCnpj: '12345678901',
        postalCode: '01001000',
        addressNumber: '100',
        phone: '11999999999',
    ),
    remoteIp: '127.0.0.1',
));
```

```php
use OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest;
use OwnerPro\Asaas\Support\DTO\Taxes;

// Create invoice with typed Taxes
Asaas::invoices()->create(new CreateInvoiceRequest(
    serviceDescription: 'Consulting services',
    observations: 'March 2026',
    value: 5000.00,
    deductions: 0.00,
    effectiveDate: '2026-03-26',
    municipalServiceName: 'Consultoria em TI',
    taxes: new Taxes(
        retainIss: false,
        iss: 2.0,
        pis: 0.65,
        cofins: 3.0,
        csll: 1.0,
        inss: 0.0,
        ir: 1.5,
    ),
));
```

```php
use OwnerPro\Asaas\Transfer\Request\CreateTransferRequest;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\Bank;

// Transfer with typed BankAccount
Asaas::transfers()->create(new CreateTransferRequest(
    value: 1000.00,
    bankAccount: new BankAccount(
        ownerName: 'Jane Doe',
        cpfCnpj: '12345678901',
        agency: '0001',
        account: '123456',
        accountDigit: '1',
        bank: new Bank(code: '001'),
        bankAccountType: 'CONTA_CORRENTE',
    ),
    operationType: 'TED',
));
```

## Pagination

List methods return `AsaasPaginatedResult`:

```php
$result = Asaas::payments()->list(['limit' => 10]);

$result->data;        // array of PaymentResponse
$result->totalCount;  // total items available
$result->hasMore;     // more pages available?
$result->limit;
$result->offset;

// Fetch next page
$nextPage = $result->next();
```

### Lazy Iteration

The `all()` method returns a Generator that auto-paginates:

```php
foreach (Asaas::payments()->all(['limit' => 100]) as $payment) {
    echo $payment->id;
}

// Or collect all at once
$allPayments = iterator_to_array(Asaas::payments()->all());
```

Unlike other methods that return result objects, `all()` throws `AsaasRequestException` if an API error occurs during pagination.

## Resources

### Payments (`payments()`)

```php
Asaas::payments()->create(array|CreatePaymentRequest $data): AsaasResult
Asaas::payments()->find(string $id): AsaasResult
Asaas::payments()->list(array $query = []): AsaasPaginatedResult
Asaas::payments()->update(string $id, array|UpdatePaymentRequest $data): AsaasResult
Asaas::payments()->delete(string $id): AsaasResult
Asaas::payments()->refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
Asaas::payments()->restore(string $id): AsaasResult
Asaas::payments()->captureAuthorized(string $id): AsaasResult
Asaas::payments()->payWithCreditCard(string $id, array|PayWithCreditCardRequest $data): AsaasResult
Asaas::payments()->receiveInCash(string $id, array|ReceivePaymentInCashRequest $data = []): AsaasResult
Asaas::payments()->undoReceivedInCash(string $id): AsaasResult
Asaas::payments()->status(string $id): AsaasResult
Asaas::payments()->billingInfo(string $id): AsaasResult
Asaas::payments()->pixQrCode(string $id): AsaasResult
Asaas::payments()->identificationField(string $id): AsaasResult
Asaas::payments()->viewingInfo(string $id): AsaasResult
Asaas::payments()->simulate(array|SimulatePaymentRequest $data): AsaasResult
Asaas::payments()->limits(): AsaasResult
Asaas::payments()->all(array $filters = []): Generator
```

### Pix Keys (`pix()`)

```php
Asaas::pix()->createKey(array|CreatePixKeyRequest $data): AsaasResult
Asaas::pix()->findKey(string $id): AsaasResult
Asaas::pix()->listKeys(array $query = []): AsaasPaginatedResult
Asaas::pix()->deleteKey(string $id): AsaasResult
Asaas::pix()->createStaticQrCode(array|CreateStaticQrCodeRequest $data = []): AsaasResult
Asaas::pix()->deleteStaticQrCode(string $id): AsaasResult
Asaas::pix()->tokenBucket(): AsaasResult
Asaas::pix()->all(array $filters = []): Generator
```

### Pix Transactions (`pixTransactions()`)

```php
Asaas::pixTransactions()->decodeQrCode(array|DecodeQrCodeRequest $data): AsaasResult
Asaas::pixTransactions()->payQrCode(array|PayQrCodeRequest $data): AsaasResult
Asaas::pixTransactions()->find(string $id): AsaasResult
Asaas::pixTransactions()->list(array $query = []): AsaasPaginatedResult
Asaas::pixTransactions()->cancel(string $id): AsaasResult
Asaas::pixTransactions()->all(array $filters = []): Generator
```

### Transfers (`transfers()`)

```php
Asaas::transfers()->create(array|CreateTransferRequest $data): AsaasResult
Asaas::transfers()->find(string $id): AsaasResult
Asaas::transfers()->list(array $query = []): AsaasPaginatedResult
Asaas::transfers()->cancel(string $id): AsaasResult
Asaas::transfers()->all(array $filters = []): Generator
```

### Webhooks (`webhooks()`)

```php
Asaas::webhooks()->create(array|CreateWebhookRequest $data): AsaasResult
Asaas::webhooks()->find(string $id): AsaasResult
Asaas::webhooks()->list(array $query = []): AsaasPaginatedResult
Asaas::webhooks()->update(string $id, array|UpdateWebhookRequest $data): AsaasResult
Asaas::webhooks()->delete(string $id): AsaasResult
Asaas::webhooks()->removeBackoff(string $id): AsaasResult
Asaas::webhooks()->all(array $filters = []): Generator
```

### Invoices (`invoices()`)

```php
Asaas::invoices()->create(array|CreateInvoiceRequest $data): AsaasResult
Asaas::invoices()->find(string $id): AsaasResult
Asaas::invoices()->list(array $query = []): AsaasPaginatedResult
Asaas::invoices()->update(string $id, array|UpdateInvoiceRequest $data): AsaasResult
Asaas::invoices()->authorize(string $id): AsaasResult
Asaas::invoices()->cancel(string $id): AsaasResult
Asaas::invoices()->all(array $filters = []): Generator
```

### Accounts (`accounts()`)

```php
Asaas::accounts()->create(array|CreateAccountRequest $data): AsaasResult
Asaas::accounts()->find(string $id): AsaasResult
Asaas::accounts()->list(array $query = []): AsaasPaginatedResult
Asaas::accounts()->listAccessTokens(string $accountId): AsaasResult
Asaas::accounts()->createAccessToken(string $accountId): AsaasResult
Asaas::accounts()->updateAccessToken(string $accountId, string $tokenId, array|UpdateAccessTokenRequest $data): AsaasResult
Asaas::accounts()->deleteAccessToken(string $accountId, string $tokenId): AsaasResult
Asaas::accounts()->all(array $filters = []): Generator
```

### Credit Cards (`creditCards()`)

```php
Asaas::creditCards()->tokenize(array|TokenizeCreditCardRequest $data): AsaasResult
Asaas::creditCards()->getPreAuthorizationConfig(): AsaasResult
Asaas::creditCards()->setPreAuthorizationConfig(array|SetPreAuthConfigRequest $data): AsaasResult
```

### Bill Payments (`billPayments()`)

```php
Asaas::billPayments()->create(array|CreateBillPaymentRequest $data): AsaasResult
Asaas::billPayments()->find(string $id): AsaasResult
Asaas::billPayments()->list(array $query = []): AsaasPaginatedResult
Asaas::billPayments()->simulate(array|SimulateBillPaymentRequest $data = []): AsaasResult
Asaas::billPayments()->cancel(string $id): AsaasResult
Asaas::billPayments()->all(array $filters = []): Generator
```

### Statements (`statements()`)

```php
Asaas::statements()->list(array $query = []): AsaasPaginatedResult
Asaas::statements()->all(array $filters = []): Generator
```

## Tolerant Responses

Response objects use a tolerant reader pattern. Known fields are typed, but unknown fields from the API are also accessible:

```php
$payment = $result->data;

// Typed properties
$payment->id;        // string
$payment->status;    // ?string

// Unknown fields (API may add new fields anytime)
$payment->someNewField;  // mixed (returns null if not present)
```

Response objects are immutable. Attempting to modify a property throws `LogicException`.

## License

MIT
