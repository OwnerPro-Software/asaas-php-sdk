# Asaas PHP SDK

Clean PHP SDK for the [Asaas](https://www.asaas.com/) payment platform API with typed request DTOs and result-based error handling.

## Requirements

- PHP 8.2+
- `illuminate/http` ^10.0|^11.0|^12.0

Works with Laravel 10, 11, or 12 (auto-discovers ServiceProvider and Facade), and also works in any PHP project without Laravel.

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

No Laravel framework needed — only `illuminate/http` as a Composer dependency:

```php
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\Environment;

$client = AsaasClient::for(apiKey: 'your-api-key');
$result = $client->payments()->find('pay_abc123');

// Override defaults
$client = AsaasClient::for(
    apiKey: 'your-api-key',
    environment: Environment::Production,
    timeout: 60,
);
```

### Multi-Tenant

For SaaS platforms where each tenant has their own Asaas API key:

```php
// In Laravel — inherits environment/timeout from config
use OwnerPro\Asaas\Asaas;
use OwnerPro\Asaas\Support\Environment;

foreach ($tenants as $tenant) {
    $client = Asaas::for(apiKey: $tenant->asaas_api_key);
    $result = $client->payments()->list();
}

// Override per-tenant
$client = Asaas::for(
    apiKey: $tenant->asaas_api_key,
    environment: Environment::Production,
);

// Standalone (without Laravel)
use OwnerPro\Asaas\AsaasClient;

foreach ($tenants as $tenant) {
    $client = AsaasClient::for(apiKey: $tenant->asaas_api_key);
    $result = $client->payments()->list();
}
```

## Result Handling

All resource methods return `AsaasResult` or `AsaasPaginatedResult`.

```php
$result = Asaas::payments()->create([...]);

if ($result->success) {
    $payment = $result->data;       // array<string, mixed>
    echo $payment['id'];
    echo $payment['status'];
} else {
    $errors = $result->errors;      // array of error details
}
```

### Throwing on Failure

```php
// Throws AsaasRequestException on failure, returns self on success
$result = Asaas::payments()->find('pay_abc123')->orFail();
$payment = $result->data;
```

### Error Handling

```php
use OwnerPro\Asaas\Support\AsaasRequestException;

try {
    Asaas::payments()->find('pay_invalid')->orFail();
} catch (AsaasRequestException $e) {
    $e->getMessage();    // First error description
    $e->statusCode;      // HTTP status code (0 for connection errors)
    $e->errors;          // Full error array from API
    $e->response;        // ?RawResponse — null for connection errors
}
```

### Raw Response Access

Every `AsaasResult` and `AsaasPaginatedResult` carries the underlying HTTP response for debugging, rate limit tracking, and Asaas support tickets:

```php
$result = Asaas::payments()->find('pay_abc123');

$result->response->status();                   // HTTP status code
$result->response->headers();                  // All response headers
$result->response->header('X-Request-Id');     // Single header (null if absent)
$result->response->body();                     // Raw response body

// Connection errors have no HTTP response
$result->response;  // null when connection failed
```

The `RawResponse` wrapper keeps your code decoupled from the underlying HTTP client.

## Enums

The SDK provides backed string enums for all domain values. Request DTOs accept both enum instances and plain strings (backward compatible). Responses return raw strings — use `EnumType::from()` when you need a typed enum.

```php
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\PaymentStatus;

// Using enums in requests (IDE autocompletion + typo prevention)
$result = Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123',
    billingType: BillingType::Pix,
    value: 150.00,
    dueDate: '2026-04-01',
));

// Plain strings still work
$result = Asaas::payments()->create([
    'customer' => 'cus_abc123',
    'billingType' => 'PIX',
    'value' => 150.00,
    'dueDate' => '2026-04-01',
]);

// Responses return strings — hydrate to enums when needed
$payment = $result->data;
$payment['status'];                          // 'PENDING'
PaymentStatus::from($payment['status']);     // PaymentStatus::Pending
```

### Available Enums

| Enum | Values |
|------|--------|
| `Payment\BillingType` | `Undefined`, `Boleto`, `CreditCard`, `DebitCard`, `Transfer`, `Deposit`, `Pix` |
| `Payment\PaymentStatus` | `Pending`, `Received`, `Confirmed`, `Overdue`, `Refunded`, `ReceivedInCash`, `RefundRequested`, `RefundInProgress`, `ChargebackRequested`, `ChargebackDispute`, `AwaitingChargebackReversal`, `DunningRequested`, `DunningReceived`, `AwaitingRiskAnalysis` |
| `Pix\PixAddressKeyType` | `Cpf`, `Cnpj`, `Email`, `Phone`, `Evp` |
| `Pix\PixAddressKeyStatus` | `AwaitingActivation`, `Active`, `AwaitingDeletion`, `AwaitingAccountDeletion`, `Deleted`, `Error` |
| `Pix\QrCodeFormat` | `All`, `Image`, `Payload` |
| `PixTransaction\PixTransactionType` | `Debit`, `Credit`, `CreditRefund`, `DebitRefund`, `DebitRefundCancellation` |
| `PixTransaction\PixTransactionStatus` | `AwaitingBalanceValidation`, `AwaitingInstantPaymentAccountBalance`, `AwaitingCriticalActionAuthorization`, `AwaitingCheckoutRiskAnalysisRequest`, `AwaitingCashInRiskAnalysisRequest`, `Scheduled`, `AwaitingRequest`, `Requested`, `Done`, `Refused`, `Cancelled` |
| `PixTransaction\PixQrCodeType` | `Static`, `Dynamic`, `DynamicWithAsaasAddressKey`, `Composite` |
| `Transfer\TransferOperationType` | `Pix`, `Ted`, `Internal` |
| `Transfer\TransferStatus` | `Pending`, `BankProcessing`, `Done`, `Cancelled`, `Failed` |
| `Invoice\InvoiceStatus` | `Scheduled`, `Authorized`, `ProcessingCancellation`, `Canceled`, `CancellationDenied`, `Error` |
| `BillPayment\BillPaymentStatus` | `Pending`, `BankProcessing`, `Paid`, `Failed`, `Cancelled`, `Refunded`, `AwaitingCheckoutRiskAnalysisRequest` |
| `Account\CompanyType` | `Mei`, `Limited`, `Individual`, `Association` |
| `Account\PersonType` | `Fisica`, `Juridica` |
| `CreditCard\CreditCardBrand` | `Visa`, `Mastercard`, `Elo`, `Diners`, `Discover`, `Amex`, `Cabal`, `Banescard`, `Credz`, `Sorocred`, `Credsystem`, `Jcb`, `Unknown` |
| `Webhook\WebhookSendType` | `Sequentially`, `NonSequentially` |
| `Webhook\WebhookEvent` | 111 event types (`PaymentCreated`, `PaymentReceived`, `TransferDone`, etc.) |
| `Statement\FinancialTransactionType` | 129 transaction types (`PaymentReceived`, `Transfer`, `BillPayment`, etc.) |
| `Support\Enums\BankAccountType` | `ContaCorrente`, `ContaPoupanca` |

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
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;

$result = Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123',
    billingType: BillingType::Pix,
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
| `CreditCard` | `CreatePaymentRequest`, `CreditCardRequest`, `PayWithCreditCardRequest` |
| `CreditCardHolderInfo` | `CreatePaymentRequest`, `CreditCardRequest`, `PayWithCreditCardRequest` |
| `BankAccount` | `TransferRequest` |
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
use OwnerPro\Asaas\Payment\BillingType;

Asaas::payments()->simulate(new SimulatePaymentRequest(
    value: 500.00,
    billingTypes: [BillingType::CreditCard, BillingType::Pix],
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
use OwnerPro\Asaas\Pix\Request\StaticQrCodeRequest;

Asaas::pix()->createStaticQrCode(new StaticQrCodeRequest(
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

`CreatePaymentRequest`, `CreditCardRequest`, `CreateInvoiceRequest`, and `TransferRequest` now accept typed nested DTOs alongside plain arrays:

```php
use OwnerPro\Asaas\CreditCard\Request\CreditCardRequest;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

// Tokenize with typed DTOs
Asaas::creditCards()->tokenize(new CreditCardRequest(
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
use OwnerPro\Asaas\Transfer\Request\TransferRequest;
use OwnerPro\Asaas\Transfer\TransferOperationType;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\Bank;
use OwnerPro\Asaas\Support\Enums\BankAccountType;

// Transfer with typed BankAccount
Asaas::transfers()->create(new TransferRequest(
    value: 1000.00,
    bankAccount: new BankAccount(
        ownerName: 'Jane Doe',
        cpfCnpj: '12345678901',
        agency: '0001',
        account: '123456',
        accountDigit: '1',
        bank: new Bank(code: '001'),
        bankAccountType: BankAccountType::ContaCorrente,
    ),
    operationType: TransferOperationType::Ted,
));
```

## Pagination

List methods return `AsaasPaginatedResult`:

```php
$result = Asaas::payments()->list(['limit' => 10]);

$result->data;        // list of arrays (each array is a raw API response)
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
    if ($payment instanceof \OwnerPro\Asaas\Support\AsaasPaginatedError) {
        // Handle error — iteration stops after this
        Log::error('Pagination failed at offset '.$payment->offset, $payment->errors);
        break;
    }

    echo $payment['id'];
}

// Or collect all at once
$allPayments = iterator_to_array(Asaas::payments()->all());
```

If an API error occurs during pagination, the Generator yields an `AsaasPaginatedError` instead of throwing. This object carries:
- `errors` — the error list from the API
- `response` — the raw HTTP response (null for connection errors)
- `offset` — the page offset that failed
- `limit` — the page size

You can opt-in to exceptions by calling `throw()` on the error object:

```php
foreach (Asaas::payments()->all() as $payment) {
    if ($payment instanceof \OwnerPro\Asaas\Support\AsaasPaginatedError) {
        $payment->orFail(); // throws AsaasRequestException
    }

    processPayment($payment);
}
```

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
Asaas::payments()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Pix Keys (`pix()`)

```php
Asaas::pix()->createKey(array|PixKeyRequest $data): AsaasResult
Asaas::pix()->findKey(string $id): AsaasResult
Asaas::pix()->listKeys(array $query = []): AsaasPaginatedResult
Asaas::pix()->deleteKey(string $id): AsaasResult
Asaas::pix()->createStaticQrCode(array|StaticQrCodeRequest $data = []): AsaasResult
Asaas::pix()->deleteStaticQrCode(string $id): AsaasResult
Asaas::pix()->tokenBucket(): AsaasResult
Asaas::pix()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Pix Transactions (`pixTransactions()`)

```php
Asaas::pixTransactions()->decodeQrCode(array|DecodeQrCodeRequest $data): AsaasResult
Asaas::pixTransactions()->payQrCode(array|PayQrCodeRequest $data): AsaasResult
Asaas::pixTransactions()->find(string $id): AsaasResult
Asaas::pixTransactions()->list(array $query = []): AsaasPaginatedResult
Asaas::pixTransactions()->cancel(string $id): AsaasResult
Asaas::pixTransactions()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Transfers (`transfers()`)

```php
Asaas::transfers()->create(array|TransferRequest $data): AsaasResult
Asaas::transfers()->find(string $id): AsaasResult
Asaas::transfers()->list(array $query = []): AsaasPaginatedResult
Asaas::transfers()->cancel(string $id): AsaasResult
Asaas::transfers()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Webhooks (`webhooks()`)

```php
Asaas::webhooks()->create(array|CreateWebhookRequest $data): AsaasResult
Asaas::webhooks()->find(string $id): AsaasResult
Asaas::webhooks()->list(array $query = []): AsaasPaginatedResult
Asaas::webhooks()->update(string $id, array|UpdateWebhookRequest $data): AsaasResult
Asaas::webhooks()->delete(string $id): AsaasResult
Asaas::webhooks()->removeBackoff(string $id): AsaasResult
Asaas::webhooks()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Invoices (`invoices()`)

```php
Asaas::invoices()->create(array|CreateInvoiceRequest $data): AsaasResult
Asaas::invoices()->find(string $id): AsaasResult
Asaas::invoices()->list(array $query = []): AsaasPaginatedResult
Asaas::invoices()->update(string $id, array|UpdateInvoiceRequest $data): AsaasResult
Asaas::invoices()->authorize(string $id): AsaasResult
Asaas::invoices()->cancel(string $id): AsaasResult
Asaas::invoices()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Accounts (`accounts()`)

```php
Asaas::accounts()->create(array|AccountRequest $data): AsaasResult
Asaas::accounts()->find(string $id): AsaasResult
Asaas::accounts()->list(array $query = []): AsaasPaginatedResult
Asaas::accounts()->listAccessTokens(string $accountId): AsaasResult
Asaas::accounts()->createAccessToken(string $accountId): AsaasResult
Asaas::accounts()->updateAccessToken(string $accountId, string $tokenId, array|AccessTokenRequest $data): AsaasResult
Asaas::accounts()->deleteAccessToken(string $accountId, string $tokenId): AsaasResult
Asaas::accounts()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Credit Cards (`creditCards()`)

```php
Asaas::creditCards()->tokenize(array|CreditCardRequest $data): AsaasResult
Asaas::creditCards()->getPreAuthorizationConfig(): AsaasResult
Asaas::creditCards()->setPreAuthorizationConfig(array|PreAuthConfigRequest $data): AsaasResult
```

### Bill Payments (`billPayments()`)

```php
Asaas::billPayments()->create(array|CreateBillPaymentRequest $data): AsaasResult
Asaas::billPayments()->find(string $id): AsaasResult
Asaas::billPayments()->list(array $query = []): AsaasPaginatedResult
Asaas::billPayments()->simulate(array|SimulateBillPaymentRequest $data = []): AsaasResult
Asaas::billPayments()->cancel(string $id): AsaasResult
Asaas::billPayments()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Statements (`statements()`)

```php
Asaas::statements()->list(array $query = []): AsaasPaginatedResult
Asaas::statements()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

## Custom Connector

All resources depend on the `Connector` interface (`OwnerPro\Asaas\Support\Connector`) rather than the concrete `AsaasConnector`. You can provide your own implementation for testing, logging, caching, or any custom behavior.

The `Connector` interface extends `HttpConnector`, which defines the four HTTP methods (`get`, `post`, `put`, `delete`). `Connector` adds `paginate` and `all`.

The `PaginatesResults` trait provides default implementations of `paginate()` and `all()` built on top of `get()`, so you only need to implement the four HTTP methods:

```php
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\PaginatesResults;

class MyLoggingConnector implements Connector
{
    use PaginatesResults;

    public function get(string $path, array $query): AsaasResult { /* ... */ }
    public function post(string $path, array $data): AsaasResult { /* ... */ }
    public function put(string $path, array $data): AsaasResult { /* ... */ }
    public function delete(string $path): AsaasResult { /* ... */ }
    // paginate() and all() are provided by the trait
}

$client = new AsaasClient(new MyLoggingConnector());
```

If you only need HTTP access without pagination, implement `HttpConnector` directly:

```php
use OwnerPro\Asaas\Support\HttpConnector;

class SimpleConnector implements HttpConnector
{
    public function get(string $path, array $query): AsaasResult { /* ... */ }
    public function post(string $path, array $data): AsaasResult { /* ... */ }
    public function put(string $path, array $data): AsaasResult { /* ... */ }
    public function delete(string $path): AsaasResult { /* ... */ }
}
```

## License

MIT
