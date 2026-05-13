# Asaas PHP SDK

Clean PHP SDK for the [Asaas](https://www.asaas.com/) payment platform API with typed request DTOs and result-based error handling.

## Requirements

- PHP 8.3+
- `illuminate/http` ^11.0|^12.0

Works with Laravel 11 or 12 (auto-discovers ServiceProvider and Facade), and also works in any PHP project without Laravel.

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
ASAAS_ENVIRONMENT=sandbox       # or "production"
ASAAS_TIMEOUT=30                # request timeout in seconds (default: 30)
ASAAS_CONNECT_TIMEOUT=10        # TCP connect timeout in seconds (default: 10)
```

> `ASAAS_API_KEY` is required. The `AsaasServiceProvider` throws `RuntimeException` the first time `AsaasClient` is resolved from the container if the key is missing or empty — keep this in mind when bootstrapping in CI or test environments where the env var may not be set.

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
    timeout: 60,           // request timeout in seconds (default: 30)
    connectTimeout: 5,     // TCP connect timeout in seconds (default: 10)
);
```

### Multi-Tenant

This SDK supports two multi-tenant patterns:

1. **Existing Asaas account (standalone)** — the tenant already has an Asaas account and provides their own apiKey. The integrator has no administrative visibility; the tenant operates independently.
2. **White-label subaccount** — the integrator creates a subaccount on behalf of the tenant via `accounts()->create()` and drives the full onboarding (KYC, commercial info, document upload, bank account) without redirecting the tenant to the Asaas panel. The subaccount keeps its own balance and KYC status; the integrator only retains administrative visibility (listing, status checks). See [My Account (`myAccount()`)](#my-account-myaccount) for the onboarding endpoints.

In both patterns, instantiate one client per tenant using their apiKey:

```php
// In Laravel — inherits environment/timeout from config
use OwnerPro\Asaas\Asaas;
use OwnerPro\Asaas\Support\Environment;

foreach ($tenants as $tenant) {
    $client = Asaas::for(apiKey: $tenant->asaas_api_key);
    $result = $client->payments()->list();
}

// Override per-tenant (any param omitted falls back to config/asaas.php)
$client = Asaas::for(
    apiKey: $tenant->asaas_api_key,
    environment: Environment::Production,
    timeout: 60,
    connectTimeout: 5,
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

> **204 No Content endpoints.** `accounts()->deleteAccessToken()` and `webhooks()->removeBackoff()` return HTTP 204 with an empty body. `$result->success` is `true` but `$result->data` is `[]` (empty array). Check `$result->success` — never `$result->data` — to decide if the call worked.

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

### Resource ID Validation

Every method that takes a resource ID (`find`, `update`, `delete`, etc.) validates the input before making the HTTP call. IDs must be 1–255 chars and match `[a-zA-Z0-9_-]+`. Empty, oversized, or otherwise malformed IDs throw `InvalidArgumentException` synchronously — they never reach the API.

```php
use InvalidArgumentException;

try {
    Asaas::payments()->find('');           // empty
    Asaas::payments()->find('pay/abc');    // illegal char
} catch (InvalidArgumentException $e) {
    // e.g. "Resource ID must be 1..255 chars; got 0."
}
```

This guards against accidental URL-segment injection from unsanitized input. Validate or sanitize user-supplied IDs upstream if you need to surface a friendlier error.

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
| `Payment\BillingType` | `Undefined`, `Boleto`, `CreditCard`, `DebitCard`, `Transfer`, `Deposit`, `Pix`, `MundipaggCielo`, `VoucherCard`, `AsaasMoney` |
| `Payment\DiscountType` | `Fixed`, `Percentage` |
| `Payment\FineType` | `Fixed`, `Percentage` |
| `Payment\PaymentDocumentType` | `Invoice`, `Contract`, `Media`, `Document`, `Spreadsheet`, `Program`, `Other` |
| `Payment\PaymentStatus` | `Pending`, `Received`, `Confirmed`, `Overdue`, `Refunded`, `ReceivedInCash`, `RefundRequested`, `RefundInProgress`, `ChargebackRequested`, `ChargebackDispute`, `AwaitingChargebackReversal`, `DunningRequested`, `DunningReceived`, `AwaitingRiskAnalysis` |
| `Pix\PixAddressKeyType` | `Cpf`, `Cnpj`, `Email`, `Phone`, `Evp` |
| `Pix\PixAddressKeyStatus` | `AwaitingActivation`, `Active`, `AwaitingDeletion`, `AwaitingAccountDeletion`, `Deleted`, `Error` |
| `Pix\QrCodeFormat` | `All`, `Image`, `Payload` |
| `PixTransaction\PixTransactionType` | `Debit`, `Credit`, `CreditRefund`, `DebitRefund`, `DebitRefundCancellation` |
| `PixTransaction\PixTransactionStatus` | `AwaitingBalanceValidation`, `AwaitingInstantPaymentAccountBalance`, `AwaitingCriticalActionAuthorization`, `AwaitingCheckoutRiskAnalysisRequest`, `AwaitingCashInRiskAnalysisRequest`, `Scheduled`, `AwaitingRequest`, `Requested`, `Done`, `Refused`, `Cancelled` |
| `PixTransaction\PixQrCodeType` | `Static`, `Dynamic`, `DynamicWithAsaasAddressKey`, `Composite` |
| `Transfer\TransferOperationType` | `Pix`, `Ted`, `Internal` |
| `Transfer\TransferRecurrenceFrequency` | `Weekly`, `Monthly` |
| `Transfer\TransferStatus` | `Pending`, `BankProcessing`, `Done`, `Cancelled`, `Failed` |
| `Invoice\InvoiceStatus` | `Scheduled`, `Authorized`, `ProcessingCancellation`, `Canceled`, `CancellationDenied`, `Error` |
| `BillPayment\BillPaymentStatus` | `Pending`, `BankProcessing`, `Paid`, `Failed`, `Cancelled`, `Refunded`, `AwaitingCheckoutRiskAnalysisRequest` |
| `Account\CompanyType` | `Mei`, `Limited`, `Individual`, `Association` |
| `Account\PersonType` | `Fisica`, `Juridica` |
| `Account\DocumentType` | 12 KYC types — `AllowBankAccountDepositStatement`, `Custom`, `EmancipationOfMinors`, `EntrepreneurRequirement`, `IdentificationSelfie`, `Identification`, `Invoice`, `MeiCertificate`, `MinutesOfConstitution`, `MinutesOfElection`, `PowerOfAttorney`, `SocialContract` |
| `Account\AccessTokenPermission` | 33 cases — `Payment`, `Transfer`, `Webhook`, `Invoice`, `Bill`, `PixDebit`, `PixCredit`, `PixAddressKey`, `PixRecurring`, `PixTransaction`, `PixAutomatic`, `Customer`, `CustomerNotification`, `PaymentRefund`, `Chargeback`, `Installment`, `Subscription`, `PaymentLink`, `Checkout`, `CreditCard`, `Anticipation`, `AnticipationConfig`, `Escrow`, `EscrowConfig`, `CreditBureau`, `PaymentDunning`, `FiscalInfo`, `MobilePhoneRecharge`, `FinancialTransaction`, `AccountInfo`, `PaymentCheckoutConfig`, `SubAccount`, `AccountDocument` |
| `Account\AccessTokenScope` | `Read`, `ReadWrite` |
| `CreditCard\CreditCardBrand` | `Visa`, `Mastercard`, `Elo`, `Diners`, `Discover`, `Amex`, `Cabal`, `Banescard`, `Credz`, `Sorocred`, `Credsystem`, `Jcb`, `Unknown` |
| `Webhook\WebhookSendType` | `Sequentially`, `NonSequentially` |
| `Webhook\WebhookEvent` | 111 event types (`PaymentCreated`, `PaymentReceived`, `TransferDone`, etc.) |
| `Statement\FinancialTransactionType` | 129 transaction types (`PaymentReceived`, `Transfer`, `BillPayment`, etc.) |
| `Support\BankAccountType` | `CheckingAccount`, `SavingsAccount` |

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
| `Callback` | `CreatePaymentRequest`, `UpdatePaymentRequest` |
| `Transfer\Request\Recurring` | `TransferRequest` (`{frequency, quantity}`; frequency = `TransferRecurrenceFrequency`) |
| `QrCodePayload` | `PayQrCodeRequest` |
| `Discount` | `CreatePaymentRequest`, `UpdatePaymentRequest` (`{value, dueDateLimitDays?, type?}`) |
| `Interest` | `CreatePaymentRequest`, `UpdatePaymentRequest` (`{value}`) |
| `Fine` | `CreatePaymentRequest`, `UpdatePaymentRequest` (`{value, type?}`) |

**Discount / Interest / Fine (since 2.0.0)** — `discount`, `interest`, and `fine` on `CreatePaymentRequest` / `UpdatePaymentRequest` are typed value objects, not floats. Each ships a `coerce()` helper that accepts a float (legacy shape, wrapped as `value`), an array, a DTO instance, `Missing::Value`, or `null` — so existing scalar callers keep working:

```php
use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\DTO\Interest;

// Legacy float still works — wrapped automatically as Discount(value: 10.0)
Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123', billingType: 'PIX', value: 200.00, dueDate: '2026-04-01',
    discount: 10.0,
));

// Typed value object with the full Asaas-documented shape
Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123', billingType: 'PIX', value: 200.00, dueDate: '2026-04-01',
    discount: new Discount(value: 10.0, dueDateLimitDays: 5, type: DiscountType::Percentage),
    interest: new Interest(value: 1.5),
    fine: new Fine(value: 2.0, type: FineType::Percentage),
));
```

Reading `$request->discount` returns `?Discount` (not `?float`) — access `$request->discount->value` for the scalar.

Example with `Split` (marketplace splits) and `Callback` (post-payment redirect):

```php
use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\Split;

Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123',
    billingType: BillingType::Pix,
    value: 200.00,
    dueDate: '2026-04-01',
    split: [
        new Split(walletId: 'wallet_partner_a', percentualValue: 70.0),
        new Split(walletId: 'wallet_partner_b', fixedValue: 30.00),
    ],
    callback: new Callback(
        successUrl: 'https://example.com/return',
        autoRedirect: true,
    ),
));
```

### New Request DTOs

Beyond `create()` and `update()`, several action methods now accept typed request objects:

**Payment actions:**

```php
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
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
    remoteIp: '203.0.113.42', // payer's IP — required for Asaas antifraud analysis
));

// Create + charge with a saved card token in one shot (`POST /payments/`)
Asaas::payments()->createWithCreditCard(new CreatePaymentRequest(
    customer: 'cus_456',
    billingType: 'CREDIT_CARD',
    value: 150.00,
    dueDate: '2026-04-01',
    remoteIp: '203.0.113.42',
    creditCardToken: 'tok_xyz', // saved-card token from a previous authorization
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

### Partial Updates: Omitting Fields

Update DTOs (`UpdatePaymentRequest`, `UpdateInvoiceRequest`, `UpdateWebhookRequest`) use `Missing::Value` as the default for every field. Omit a field to leave it untouched; pass a value to change it. The Asaas spec marks every request-body field as `nullable: false`, so explicit `null` is **not** supported — passing `null` to a typed field now raises `TypeError` instead of leaking `{"field": null}` onto the wire (which the API rejects).

```php
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;

// 1. Don't change — omit the field (default):
Asaas::payments()->update('pay_123', new UpdatePaymentRequest(
    value: 200.00,
    // description not passed → field not sent → API keeps current value
));

// 2. Set a new value:
Asaas::payments()->update('pay_123', new UpdatePaymentRequest(
    description: 'New description',
));
```

Array shape works identically — only keys present in the array reach the wire:

```php
// Only updates value, description untouched:
Asaas::payments()->update('pay_123', ['value' => 200.00]);
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
    updatePayment: true, // since 2.0.0 — auto-discount the tax retention from the linked payment value
));
```

```php
use OwnerPro\Asaas\Transfer\Request\TransferRequest;
use OwnerPro\Asaas\Transfer\TransferOperationType;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\DTO\Bank;
use OwnerPro\Asaas\Support\BankAccountType;

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
        bankAccountType: BankAccountType::CheckingAccount,
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

You can opt-in to exceptions by calling `orFail()` on the error object:

```php
foreach (Asaas::payments()->all() as $payment) {
    if ($payment instanceof \OwnerPro\Asaas\Support\AsaasPaginatedError) {
        $payment->orFail(); // throws AsaasRequestException
    }

    processPayment($payment);
}
```

## Resources

> **Response shape.** Every resource method returns `AsaasResult` with `$data`
> typed as `array<string, mixed>` — the SDK passes Asaas's JSON response through
> verbatim and does **not** decode it into typed response objects. To learn
> which fields each endpoint returns (e.g. `id`, `status`, `bankSlipUrl`,
> `netValue`, `dateCreated`), consult either the OpenAPI spec under
> `specs/domains/<domain>.json` or the public Asaas docs at
> <https://docs.asaas.com/>. Nested sub-objects observed on the response side
> (`creditCard`, `discount`, `fine`, `interest`, `callback`, `escrow`,
> `chargeback`, etc.) follow the same `array<string, mixed>` rule — the SDK's
> request-side DTOs in `OwnerPro\Asaas\Support\DTO\*` are reference-only for
> field names, not response value objects.

### Payments (`payments()`)

```php
Asaas::payments()->create(array|CreatePaymentRequest $data): AsaasResult
Asaas::payments()->createWithCreditCard(array|CreatePaymentRequest $data): AsaasResult
Asaas::payments()->find(string $id): AsaasResult
Asaas::payments()->list(array $query = []): AsaasPaginatedResult
Asaas::payments()->update(string $id, array|UpdatePaymentRequest $data): AsaasResult
Asaas::payments()->delete(string $id): AsaasResult
Asaas::payments()->refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
Asaas::payments()->listRefunds(string $id): AsaasResult
Asaas::payments()->refundBankSlip(string $id): AsaasResult
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
Asaas::payments()->getChargeback(string $id): AsaasResult
Asaas::payments()->getEscrow(string $id): AsaasResult
Asaas::payments()->finishEscrow(string $id): AsaasResult
Asaas::payments()->simulate(array|SimulatePaymentRequest $data): AsaasResult
Asaas::payments()->limits(): AsaasResult
Asaas::payments()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)

// Payment documents (multipart upload + CRUD)
Asaas::payments()->uploadDocument(
    string $paymentId,
    string|resource $file,
    PaymentDocumentType|string $type,
    bool $availableAfterPayment,
    string $filename,
): AsaasResult
Asaas::payments()->listDocuments(string $paymentId): AsaasPaginatedResult
Asaas::payments()->findDocument(string $paymentId, string $documentId): AsaasResult
Asaas::payments()->updateDocument(string $paymentId, string $documentId, array|UpdatePaymentDocumentRequest $data): AsaasResult
Asaas::payments()->deleteDocument(string $paymentId, string $documentId): AsaasResult

// Split lookup (paid by you / received by you)
Asaas::payments()->listSplitsPaid(array $query = []): AsaasPaginatedResult
Asaas::payments()->findSplitPaid(string $id): AsaasResult
Asaas::payments()->listSplitsReceived(array $query = []): AsaasPaginatedResult
Asaas::payments()->findSplitReceived(string $id): AsaasResult
```

### Lean Payments (`leanPayments()`)

Slim-response variants of the standard payment endpoints — same request DTOs, smaller response payloads. Useful for high-throughput create/find flows where you don't need every field back.

```php
Asaas::leanPayments()->create(array|CreatePaymentRequest $data): AsaasResult
Asaas::leanPayments()->createWithCreditCard(array|CreatePaymentRequest $data): AsaasResult
Asaas::leanPayments()->list(array $query = []): AsaasPaginatedResult
Asaas::leanPayments()->find(string $id): AsaasResult
Asaas::leanPayments()->update(string $id, array|UpdatePaymentRequest $data): AsaasResult
Asaas::leanPayments()->delete(string $id): AsaasResult
Asaas::leanPayments()->captureAuthorized(string $id): AsaasResult
Asaas::leanPayments()->restore(string $id): AsaasResult
Asaas::leanPayments()->refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
Asaas::leanPayments()->receiveInCash(string $id, array|ReceivePaymentInCashRequest $data = []): AsaasResult
Asaas::leanPayments()->undoReceivedInCash(string $id): AsaasResult
Asaas::leanPayments()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

#### Credit card pre-authorization (two-step capture)

Set `authorizeOnly: true` on `CreatePaymentRequest` (or via array) to reserve the value without capturing it. Capture later with `captureAuthorized($id)`:

```php
$result = Asaas::payments()->create(new CreatePaymentRequest(
    customer: 'cus_abc123',
    billingType: 'CREDIT_CARD',
    value: 200.00,
    dueDate: '2026-04-01',
    creditCard: new CreditCard(/* ... */),
    creditCardHolderInfo: new CreditCardHolderInfo(/* ... */),
    authorizeOnly: true,
));

Asaas::payments()->captureAuthorized($result->data['id']);
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

// Pix recurrings (scheduled outflow)
Asaas::pixTransactions()->listRecurrings(array $query = []): AsaasPaginatedResult
Asaas::pixTransactions()->findRecurring(string $id): AsaasResult
Asaas::pixTransactions()->cancelRecurring(string $id): AsaasResult
Asaas::pixTransactions()->listRecurringItems(string $id, array $query = []): AsaasPaginatedResult
Asaas::pixTransactions()->cancelRecurringItem(string $itemId): AsaasResult
Asaas::pixTransactions()->allRecurrings(array $filters = []): Generator (yields array|AsaasPaginatedError)
Asaas::pixTransactions()->allRecurringItems(string $id, array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Pix Automático (`pixAutomatic()`)

Authorized recurring debits (Pix Automático) — payer authorizes the receiver to charge periodically.

```php
Asaas::pixAutomatic()->createAuthorization(array|AuthorizationRequest $data): AsaasResult
Asaas::pixAutomatic()->listAuthorizations(array $query = []): AsaasPaginatedResult
Asaas::pixAutomatic()->findAuthorization(string $id): AsaasResult
Asaas::pixAutomatic()->cancelAuthorization(string $id): AsaasResult
Asaas::pixAutomatic()->listPaymentInstructions(array $query = []): AsaasPaginatedResult
Asaas::pixAutomatic()->findPaymentInstruction(string $id): AsaasResult
Asaas::pixAutomatic()->allAuthorizations(array $filters = []): Generator (yields array|AsaasPaginatedError)
Asaas::pixAutomatic()->allPaymentInstructions(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Transfers (`transfers()`)

```php
Asaas::transfers()->create(array|TransferRequest $data): AsaasResult
Asaas::transfers()->createInternal(array|InternalTransferRequest $data): AsaasResult
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

> **Asaas requires `interrupted` on create.** The Asaas API rejects `POST /v3/webhooks` with HTTP 400 (`"O parâmetro poolInterrupted deve ser informado"` — `poolInterrupted` is the Asaas validator's internal variable name; the documented and accepted JSON field is `interrupted`) when the field is absent. `CreateWebhookRequest` defaults `interrupted` to `false` so the request is always accepted. `UpdateWebhookRequest::interrupted` follows the usual partial-update pattern (`Missing` = not sent).

### Webhook Verification

When you create a webhook with an `authToken`, Asaas sends that token in the `asaas-access-token` header on every delivery. Use `WebhookVerifier` to validate incoming requests with a timing-safe comparison:

```php
use OwnerPro\Asaas\Webhook\WebhookVerifier;

$verifier = new WebhookVerifier(authToken: 'your-webhook-auth-token');

// Verify the token (timing-safe via hash_equals)
if (! $verifier->verify($request->header('asaas-access-token', ''))) {
    abort(401);
}

// Optional: verify the request came from a known Asaas IP
if (! $verifier->isFromAsaas($request->ip())) {
    abort(403);
}
```

The default known Asaas IPs are `52.67.12.206`, `18.230.8.159`, `54.94.136.112`, and `54.94.183.101`. You can override them if Asaas updates their IP list:

```php
$verifier = new WebhookVerifier(
    authToken: 'your-webhook-auth-token',
    trustedIps: [...WebhookVerifier::DEFAULT_IPS, '10.0.0.1'],
);
```

### Invoices (`invoices()`)

```php
Asaas::invoices()->create(array|CreateInvoiceRequest $data): AsaasResult
Asaas::invoices()->find(string $id): AsaasResult
Asaas::invoices()->list(array $query = []): AsaasPaginatedResult
Asaas::invoices()->update(string $id, array|UpdateInvoiceRequest $data): AsaasResult
Asaas::invoices()->authorize(string $id): AsaasResult
Asaas::invoices()->cancel(string $id, array|CancelInvoiceRequest|null $data = null): AsaasResult
Asaas::invoices()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Accounts (`accounts()`)

```php
Asaas::accounts()->create(array|AccountRequest $data): AsaasResult
Asaas::accounts()->find(string $id): AsaasResult
Asaas::accounts()->list(array $query = []): AsaasPaginatedResult
Asaas::accounts()->listAccessTokens(string $accountId): AsaasResult
Asaas::accounts()->findAccessToken(string $accountId, string $tokenId): AsaasResult
Asaas::accounts()->createAccessToken(string $accountId, array|CreateAccessTokenRequest|null $data = null): AsaasResult
Asaas::accounts()->updateAccessToken(string $accountId, string $tokenId, array|UpdateAccessTokenRequest $data): AsaasResult
Asaas::accounts()->deleteAccessToken(string $accountId, string $tokenId): AsaasResult
Asaas::accounts()->escrowConfig(string $accountId): AsaasResult
Asaas::accounts()->setEscrowConfig(string $accountId, array|EscrowConfigRequest $data): AsaasResult
Asaas::accounts()->defaultEscrowConfig(): AsaasResult
Asaas::accounts()->setDefaultEscrowConfig(array|EscrowConfigRequest $data): AsaasResult
Asaas::accounts()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

#### Subaccount API key permissions

By default, Asaas issues a brand-new subaccount key with **all permissions in `READ_WRITE`** (which is fine for most cases). To start a subaccount locked down to a narrow scope — or to widen/shrink an existing key later — pass `accessTokenConfig` on creation, or `permissions` on update:

```php
use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\Request\AccessTokenConfig;
use OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig;
use OwnerPro\Asaas\Account\Request\AccountRequest;
use OwnerPro\Asaas\Account\Request\UpdateAccessTokenRequest;

// On subaccount creation — initial key with a curated scope
$result = Asaas::accounts()->create(new AccountRequest(
    name: 'Subconta Integrada',
    email: 'integrator@example.com',
    cpfCnpj: '12345678000199',
    mobilePhone: '11999999999',
    incomeValue: 25000.0,
    address: 'Av Paulista',
    addressNumber: '1000',
    province: 'Bela Vista',
    postalCode: '01310100',
    accessTokenConfig: new AccessTokenConfig(
        name: 'Chave de Integração',
        permissions: [
            new AccessTokenPermissionConfig(name: AccessTokenPermission::Payment, scope: AccessTokenScope::ReadWrite),
            new AccessTokenPermissionConfig(name: AccessTokenPermission::Transfer, scope: AccessTokenScope::ReadWrite),
            new AccessTokenPermissionConfig(name: AccessTokenPermission::Webhook,  scope: AccessTokenScope::ReadWrite),
        ],
    ),
));

// Later — widen an existing key's permissions
Asaas::accounts()->updateAccessToken('acc_123', 'tok_1', new UpdateAccessTokenRequest(
    name: 'Chave de Integração',
    enabled: true,
    expirationDate: '2026-12-31 23:59:59',
    permissions: [
        new AccessTokenPermissionConfig(name: 'TRANSFER', scope: 'READ_WRITE'),
    ],
));
```

> **Required on update.** `UpdateAccessTokenRequest` makes `name`, `enabled` and `expirationDate` mandatory in the constructor — the Asaas `PUT /accounts/{id}/accessTokens/{tokenId}` rejects partial updates. `CreateAccessTokenRequest` keeps every field optional because `POST` accepts a bare body (Asaas applies its defaults).

`AccessTokenPermission` covers all 33 permission codes documented by Asaas (`PAYMENT`, `TRANSFER`, `WEBHOOK`, `PIX_*`, `INVOICE`, `BILL`, etc.); `AccessTokenScope` is `READ` or `READ_WRITE`. Both DTOs accept the enum **or** the raw string for forward-compat.

#### Permission ↔ endpoint mapping (heuristic)

> **Heuristic mapping — not normative.** Asaas does not publish an official permission ↔ endpoint correspondence: the OpenAPI spec declares `security` only at the top of each domain file (no per-operation `security` or `x-permission` extension), and the public docs page that once described the mapping no longer resolves. The table below is our best inference from the enum names and Asaas API domains. Treat it as best-effort documentation, not contract: validate in sandbox before restricting a production key, and report drift via a GitHub issue if a restricted key returns `401/403` on an endpoint listed here.

| Permission | Endpoints (heuristic) | SDK Resource |
|---|---|---|
| `Customer` | `/v3/customers/*` | not exposed — use `Connector` directly |
| `CustomerNotification` | `/v3/customers/{id}/notifications`, `/v3/notifications/*` | not exposed — use `Connector` directly |
| `Payment` | `/v3/payments`, `/v3/payments/{id}`, `/v3/payments/{id}/status`, `/v3/payments/{id}/billingInfo`, `/v3/payments/{id}/viewingInfo`, `/v3/payments/{id}/identificationField`, `/v3/payments/{id}/pixQrCode`, `/v3/payments/{id}/receiveInCash`, `/v3/payments/{id}/undoReceivedInCash`, `/v3/payments/{id}/captureAuthorizedPayment`, `/v3/payments/{id}/payWithCreditCard`, `/v3/payments/{id}/refund`, `/v3/payments/{id}/restore`, `/v3/payments/limits`, `/v3/payments/simulate`, `/v3/lean/payments/*` | `PaymentResource`, `LeanPaymentResource` |
| `PaymentRefund` | `/v3/payments/{id}/refunds`, `/v3/payments/{id}/bankSlip/refund` | `PaymentResource::refunds*` |
| `Chargeback` | `/v3/chargebacks/*`, `/v3/payments/{id}/chargeback` | partial — `PaymentResource::chargeback` |
| `Installment` | `/v3/installments/*` | not exposed — use `Connector` directly |
| `Subscription` | `/v3/subscriptions/*` | not exposed — use `Connector` directly |
| `PixCredit` | `/v3/payments/{id}/pixQrCode` (receive via Pix); dynamic QR Code | `PaymentResource::pixQrCode` |
| `PaymentLink` | `/v3/paymentLinks/*` | not exposed — use `Connector` directly |
| `Checkout` | `/v3/checkouts/*` | not exposed — use `Connector` directly |
| `CreditCard` | `/v3/creditCard/tokenizeCreditCard`, `/v3/creditCard/preAuthorization/config` | `CreditCardResource` |
| `Anticipation` | `/v3/anticipations`, `/v3/anticipations/{id}`, `/v3/anticipations/{id}/cancel`, `/v3/anticipations/simulate`, `/v3/anticipations/limits` | not exposed — use `Connector` directly |
| `AnticipationConfig` | `/v3/anticipations/configurations` | not exposed — use `Connector` directly |
| `Escrow` | `/v3/escrow/{id}/finish`, `/v3/payments/{id}/escrow`, `/v3/accounts/{id}/escrow` | partial — `AccountResource::getEscrow`/`updateEscrow` |
| `EscrowConfig` | `/v3/accounts/escrow`, `/v3/accounts/{id}/escrow` (PUT/POST config) | `AccountResource::*EscrowConfig` |
| `CreditBureau` | `/v3/creditBureauReport`, `/v3/creditBureauReport/{id}` | not exposed — use `Connector` directly |
| `PaymentDunning` | `/v3/paymentDunnings/*` | not exposed — use `Connector` directly |
| `FiscalInfo` | `/v3/fiscalInfo/*` (config, services, NBS, taxes, federalServiceCodes, taxClassificationCodes, taxSituationCodes, operationIndicatorCodes, municipalOptions, nationalPortal) | `FiscalInfoResource` |
| `Invoice` | `/v3/invoices`, `/v3/invoices/{id}`, `/v3/invoices/{id}/authorize`, `/v3/invoices/{id}/cancel` | `InvoiceResource` |
| `PixDebit` | `/v3/pix/qrCodes/decode`, `/v3/pix/qrCodes/pay` (pay) | `PixTransactionResource::decode`/`pay` |
| `PixAddressKey` | `/v3/pix/addressKeys`, `/v3/pix/addressKeys/{id}`, `/v3/pix/tokenBucket/addressKey` | `PixResource::*Key` |
| `PixRecurring` | `/v3/pix/transactions/recurrings`, `/v3/pix/transactions/recurrings/{id}`, `/v3/pix/transactions/recurrings/{id}/cancel`, `/v3/pix/transactions/recurrings/{id}/items`, `/v3/pix/transactions/recurrings/items/{id}/cancel` | `PixTransactionResource::recurring*` |
| `PixTransaction` | `/v3/pix/transactions`, `/v3/pix/transactions/{id}`, `/v3/pix/transactions/{id}/cancel`, `/v3/pix/qrCodes/static`, `/v3/pix/qrCodes/static/{id}` | `PixTransactionResource`, `PixResource::*StaticQr*` |
| `PixAutomatic` | `/v3/pix/automatic/authorizations`, `/v3/pix/automatic/authorizations/{id}`, `/v3/pix/automatic/paymentInstructions`, `/v3/pix/automatic/paymentInstructions/{id}` | `PixAutomaticResource` |
| `Transfer` | `/v3/transfers`, `/v3/transfers/{id}`, `/v3/transfers/{id}/cancel` | `TransferResource` |
| `Bill` | `/v3/bill`, `/v3/bill/{id}`, `/v3/bill/{id}/cancel`, `/v3/bill/simulate` | `BillPaymentResource` |
| `MobilePhoneRecharge` | `/v3/mobilePhoneRecharges/*` | not exposed — use `Connector` directly |
| `FinancialTransaction` | `/v3/financialTransactions`, `/v3/finance/balance`, `/v3/finance/payment/statistics`, `/v3/finance/split/statistics` | `StatementResource` |
| `AccountInfo` | `/v3/myAccount/`, `/v3/myAccount/accountNumber`, `/v3/myAccount/commercialInfo/`, `/v3/myAccount/fees/`, `/v3/myAccount/status/`, `/v3/wallets/` | `MyAccountResource` (partial) |
| `PaymentCheckoutConfig` | `/v3/myAccount/paymentCheckoutConfig/` | `MyAccountResource::*PaymentCheckoutConfig` |
| `Webhook` | `/v3/webhooks`, `/v3/webhooks/{id}`, `/v3/webhooks/{id}/removeBackoff` | `WebhookResource` |
| `SubAccount` | `/v3/accounts`, `/v3/accounts/{id}`, `/v3/accounts/{id}/accessTokens`, `/v3/accounts/{id}/accessTokens/{accessTokenId}` | `AccountResource` |
| `AccountDocument` | `/v3/myAccount/documents`, `/v3/myAccount/documents/{id}`, `/v3/myAccount/documents/files/{id}` | `MyAccountResource::*Document*` |

Ambiguities worth flagging when validating in sandbox: `PIX_CREDIT` vs `PIX_DEBIT` (receive vs pay via Pix), `ESCROW` vs `ESCROW_CONFIG` (operations vs configuration), `ANTICIPATION` vs `ANTICIPATION_CONFIG` (analogous), and `ACCOUNT_INFO` vs `SUB_ACCOUNT` (read of own account vs CRUD over child accounts). The path `/v3/payments/{id}/chargeback` lives under the `Payment` URL prefix but is expected to require `CHARGEBACK` (resource-name wins over path-prefix).

### My Account (`myAccount()`)

Operates on the **current** account behind the apiKey — used for tenant onboarding (KYC, commercial info, document upload, bank account). Must be called with the **subaccount's** apiKey, not the master.

```php
Asaas::myAccount()->status(): AsaasResult
Asaas::myAccount()->accountNumber(): AsaasResult
Asaas::myAccount()->fees(): AsaasResult
Asaas::myAccount()->commercialInfo(): AsaasResult
Asaas::myAccount()->updateCommercialInfo(array|CommercialInfoRequest $data): AsaasResult
Asaas::myAccount()->documents(): AsaasResult
Asaas::myAccount()->uploadDocumentFile(string $documentId, string|resource $file, DocumentType|string $type, string $filename): AsaasResult
Asaas::myAccount()->findDocumentFile(string $fileId): AsaasResult
Asaas::myAccount()->updateDocumentFile(string $fileId, string|resource $file, DocumentType|string $type, string $filename): AsaasResult
Asaas::myAccount()->deleteDocumentFile(string $fileId): AsaasResult
Asaas::myAccount()->bankAccount(): AsaasResult
Asaas::myAccount()->updateBankAccount(array|AccountBankAccountRequest $data): AsaasResult
Asaas::myAccount()->paymentCheckoutConfig(): AsaasResult
Asaas::myAccount()->updatePaymentCheckoutConfig(
    array|PaymentCheckoutConfigRequest $data,
    string|resource|null $logoFile = null,
    ?string $logoFilename = null,
): AsaasResult
Asaas::myAccount()->wallets(array $query = []): AsaasPaginatedResult
Asaas::myAccount()->delete(array|DeleteAccountRequest $data): AsaasResult
```

> **Out of scope.** Sandbox-only endpoints such as `POST /sandbox/myAccount/approve`
> (auto-approval shortcut for testing) are intentionally not exposed: they have no
> production counterpart and would only obscure the real KYC flow. Use the
> `Connector` directly if you need to reach a sandbox-only path in your test
> harness. `bankAccount()` / `updateBankAccount()` hit
> `GET|POST /v3/myAccount/bankAccountInfo`, which Asaas accepts in production but
> does not formally document in `specs/domains/my-account.json` — kept here because
> the subaccount onboarding flow depends on them.

#### Subaccount onboarding (white label)

After `accounts()->create()` returns a new subaccount, the tenant must complete KYC, send commercial info, and register a bank account before they can transact. Drive the full flow without redirecting the tenant to the Asaas panel by instantiating a tenant-scoped client with the **subaccount's apiKey** and calling `myAccount()`.

```php
use OwnerPro\Asaas\Account\DocumentType;
use OwnerPro\Asaas\Account\Request\AccountBankAccountRequest;
use OwnerPro\Asaas\Account\Request\CommercialInfoRequest;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Account\CompanyType;
use OwnerPro\Asaas\Support\BankAccountType;
use OwnerPro\Asaas\Support\Environment;

$tenantClient = AsaasClient::for(
    apiKey: $tenant->asaas_api_key,
    environment: Environment::Production,
);

// 1. Pull current onboarding status
$status = $tenantClient->myAccount()->status();
// $status->data: ['general' => ..., 'commercialInfo' => ..., 'documentation' => ..., 'bankAccountInfo' => ...]

// 2. Update commercial info (partial — only changed fields)
$tenantClient->myAccount()->updateCommercialInfo(new CommercialInfoRequest(
    incomeValue: 12000.0,
    companyType: CompanyType::Limited,
));

// 3. Upload required KYC documents
$documents = $tenantClient->myAccount()->documents();
$documentId = $documents->data['data'][0]['id'];

$tenantClient->myAccount()->uploadDocumentFile(
    documentId: $documentId,
    file: fopen('/tmp/rg.png', 'rb'),
    type: DocumentType::Identification,
    filename: 'rg.png',
);

// 4. Set the bank account where withdrawals will land
$tenantClient->myAccount()->updateBankAccount(new AccountBankAccountRequest(
    bankCode: '341',
    agency: '1234',
    account: '56789',
    accountDigit: '0',
    accountType: BankAccountType::CheckingAccount,
));
```

Listen to the `ACCOUNT_STATUS_*` webhook events (already in `WebhookEvent`) to react to approvals and rejections asynchronously.

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
Asaas::statements()->balance(): AsaasResult
Asaas::statements()->paymentStatistics(array $query = []): AsaasResult
Asaas::statements()->splitStatistics(array $query = []): AsaasResult
Asaas::statements()->all(array $filters = []): Generator (yields array|AsaasPaginatedError)
```

### Fiscal Info (`fiscalInfo()`)

Configures fiscal data and digital certificates for NFS-e (Nota Fiscal de Serviço eletrônica) issuance, plus lookup of municipal/federal/national service classification codes.

```php
Asaas::fiscalInfo()->recover(): AsaasResult
Asaas::fiscalInfo()->save(
    array|FiscalInfoRequest $data,
    string|resource|null $certificateFile = null,
    ?string $certificateFilename = null,
): AsaasResult
Asaas::fiscalInfo()->municipalOptions(): AsaasResult
Asaas::fiscalInfo()->services(array $query = []): AsaasPaginatedResult
Asaas::fiscalInfo()->federalServiceCodes(array $query = []): AsaasPaginatedResult
Asaas::fiscalInfo()->nbsCodes(array $query = []): AsaasPaginatedResult
Asaas::fiscalInfo()->operationIndicatorCodes(array $query = []): AsaasPaginatedResult
Asaas::fiscalInfo()->taxClassificationCodes(array $query = []): AsaasPaginatedResult
Asaas::fiscalInfo()->taxSituationCodes(array $query = []): AsaasPaginatedResult
Asaas::fiscalInfo()->configureNationalPortal(bool $enabled): AsaasResult
```

`save()` is a `multipart/form-data` POST. Pass `$certificateFile` (binary string or file resource) only when uploading an A1 digital certificate; the form-only call works the same way without it. `email` and `simplesNacional` are the only required fields on `FiscalInfoRequest` for a **first save**; re-saves accept any subset (`FiscalInfoRequest` is a partial-update DTO — omitted fields never reach the wire).

> **Server-side defaults.** `simplesNacional` and `culturalProjectsPromoter` default to `true` on Asaas when the field is omitted from the body. The SDK deliberately omits them on partial updates so a re-save never silently overwrites a value the consumer set in a previous call. If you need stability across Asaas-side default changes, pin the value at every call site instead of relying on the omission semantics.

## Date formats

Asaas expects two distinct string formats for date fields. Mismatch (`T`, `Z`, timezones) is rejected with HTTP 400 — the SDK does **not** parse `DateTimeInterface` for you; pass already-formatted strings.

| DTO field(s) | Format | Example |
|---|---|---|
| `CreatePaymentRequest::$dueDate`, `UpdatePaymentRequest::$dueDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `ReceivePaymentInCashRequest::$paymentDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `AccountRequest::$birthDate`, `CommercialInfoRequest::$birthDate` | `YYYY-MM-DD` | `'1985-06-15'` |
| `CreateInvoiceRequest::$effectiveDate`, `UpdateInvoiceRequest::$effectiveDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `TransferRequest::$scheduleDate`, `PayQrCodeRequest::$scheduleDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `BankAccount::$ownerBirthDate` (via `TransferRequest::$bankAccount`) | `YYYY-MM-DD` | `'1990-05-15'` |
| `DecodeQrCodeRequest::$expectedPaymentDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `AuthorizationRequest::$startDate`, `AuthorizationRequest::$finishDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `CreateBillPaymentRequest::$dueDate` | `YYYY-MM-DD` | `'2026-04-01'` |
| `CreateAccessTokenRequest::$expirationDate`, `UpdateAccessTokenRequest::$expirationDate` | `YYYY-MM-DD HH:MM:SS` | `'2026-12-31 23:59:59'` |
| `StaticQrCodeRequest::$expirationDate` | `YYYY-MM-DD HH:MM:SS` | `'2026-12-31 23:59:59'` |

## Available filters per list endpoint

Every `list($query = [])` method accepts an associative array of filters. Below is the canonical set of filters accepted by the Asaas API, per resource. Pass any combination as `$query`; the SDK forwards them verbatim (`offset` / `limit` are wired by `paginate()` and rarely need to be set manually).

### `payments()->list()` / `leanPayments()->list()` — `GET /v3/payments` and `/v3/lean/payments`

| Filter | Type | Notes |
|---|---|---|
| `customer` | string | Customer ID |
| `customerGroupName` | string | Group name |
| `billingType` | string | `BOLETO`, `PIX`, `CREDIT_CARD`, `UNDEFINED` |
| `status` | string | Payment status |
| `subscription` | string | Subscription ID |
| `installment` | string | Installment ID |
| `externalReference` | string | Integrator-controlled reference |
| `paymentDate` | string `YYYY-MM-DD` | Exact payment date |
| `invoiceStatus` | string | Linked invoice status |
| `estimatedCreditDate` | string `YYYY-MM-DD` | Expected credit date |
| `pixQrCodeId` | string | Linked QR code ID |
| `anticipated` | bool | Already-anticipated flag |
| `anticipable` | bool | Eligible for anticipation |
| `dateCreated[ge]`, `dateCreated[le]` | string `YYYY-MM-DD` | Created-at range |
| `paymentDate[ge]`, `paymentDate[le]` | string `YYYY-MM-DD` | Payment date range |
| `estimatedCreditDate[ge]`, `estimatedCreditDate[le]` | string `YYYY-MM-DD` | Estimated credit range |
| `dueDate[ge]`, `dueDate[le]` | string `YYYY-MM-DD` | Due-date range |
| `user` | string | Created-by user ID |
| `checkoutSession` | string | Checkout session ID |

### `payments()->listSplitsPaid()` / `listSplitsReceived()`

| Filter | Type | Notes |
|---|---|---|
| `paymentId` | string | Source payment |
| `status` | string | Split status |
| `paymentConfirmedDate[ge]`, `paymentConfirmedDate[le]` | string `YYYY-MM-DD` | Confirmation range |
| `creditDate[ge]`, `creditDate[le]` | string `YYYY-MM-DD` | Credit range |

### `accounts()->list()` — `GET /v3/accounts`

| Filter | Type | Notes |
|---|---|---|
| `cpfCnpj` | string | CPF/CNPJ exact match |
| `email` | string | Account email |
| `name` | string | Account name |
| `walletId` | string | Wallet ID |

### `invoices()->list()` — `GET /v3/invoices`

| Filter | Type | Notes |
|---|---|---|
| `effectiveDate[Ge]`, `effectiveDate[Le]` | string `YYYY-MM-DD` | Effective-date range (capitalised in spec) |
| `payment` | string | Payment ID |
| `installment` | string | Installment ID |
| `externalReference` | string | Reference |
| `status` | string | Invoice status |
| `customer` | string | Customer ID |

### `transfers()->list()` — `GET /v3/transfers`

| Filter | Type | Notes |
|---|---|---|
| `dateCreated[ge]`, `dateCreated[le]` | string `YYYY-MM-DD` | Created-at range |
| `transferDate[ge]`, `transferDate[le]` | string `YYYY-MM-DD` | Transfer-date range |
| `type` | string | `PIX`, `TED`, `INTERNAL` |

### `pix()->listKeys()` — `GET /v3/pix/addressKeys`

| Filter | Type | Notes |
|---|---|---|
| `status` | string | Key status |
| `statusList` | string | Comma-separated status list |

### `pixTransactions()->list()` — `GET /v3/pix/transactions`

| Filter | Type | Notes |
|---|---|---|
| `status` | string | Transaction status |
| `type` | string | Transaction type |
| `endToEndIdentifier` | string | EndToEndId |

### `pixTransactions()->listRecurrings()` — `GET /v3/pix/transactions/recurrings`

| Filter | Type | Notes |
|---|---|---|
| `status` | string | Status |
| `value` | float | Exact value |
| `searchText` | string | Free-text search |

### `pixAutomatic()->listAuthorizations()` — `GET /v3/pix/automatic/authorizations`

| Filter | Type | Notes |
|---|---|---|
| `status` | string | Authorization status |
| `customerId` | string | Customer ID |

### `pixAutomatic()->listPaymentInstructions()` — `GET /v3/pix/automatic/paymentInstructions`

| Filter | Type | Notes |
|---|---|---|
| `authorizationId` | string | Source authorization |
| `customerId` | string | Customer ID |
| `paymentId` | string | Linked payment |
| `status` | string | Instruction status |

### `statements()->list()` — `GET /v3/financialTransactions`

| Filter | Type | Notes |
|---|---|---|
| `startDate`, `finishDate` | string `YYYY-MM-DD` | Inclusive range |
| `order` | string | `asc` / `desc` |

`billPayments()->list()` and `webhooks()->list()` only support `offset` / `limit`. `fiscalInfo()` lookup endpoints (`federalServiceCodes`, `nbsCodes`, `services`, `taxClassificationCodes`, `taxSituationCodes`, `operationIndicatorCodes`) accept `code` / `description` / `codeDescription` / `taxSituationCode` depending on the endpoint.

## Custom Connector

All resources depend on the `Connector` interface (`OwnerPro\Asaas\Support\Connector`) rather than the concrete `AsaasConnector`. You can provide your own implementation for testing, logging, caching, or any custom behavior.

The `Connector` interface defines seven methods: the four HTTP verbs (`get`, `post`, `put`, `delete`), `postMultipart` for file uploads, plus `paginate` and `all`. The `PaginatesResults` trait provides default implementations of `paginate()` and `all()` built on top of `get()`, so you only need to implement the five HTTP methods:

```php
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\PaginatesResults;

class MyLoggingConnector implements Connector
{
    use PaginatesResults;

    public function get(string $path, array $query = []): AsaasResult { /* ... */ }
    public function post(string $path, array $data = []): AsaasResult { /* ... */ }
    public function put(string $path, array $data = []): AsaasResult { /* ... */ }
    public function delete(string $path): AsaasResult { /* ... */ }
    public function postMultipart(string $path, array $data, array $files = []): AsaasResult { /* ... */ }
    // paginate() and all() are provided by the trait
}

$client = new AsaasClient(new MyLoggingConnector());
```

## Testing your integration

The SDK ships with a first-class test fake. Use it to assert outgoing requests and stub responses without touching the network.

### Construction

```php
use OwnerPro\Asaas\AsaasClient;
use Illuminate\Support\Facades\Http;

// Empty fake — every unmatched request throws NoMatchingStubException (loud).
$asaas = AsaasClient::fake();

// Stub via constructor.
$asaas = AsaasClient::fake([
    'payments'      => ['id' => 'pay_123', 'status' => 'PENDING'],
    'payments/*'    => ['id' => 'pay_123'],
    'pix/qrCodes/*' => Http::sequence()->push([...])->push([...]),
]);

// Or fluent (chainable).
$asaas = AsaasClient::fake()
    ->stub('webhooks', ['id' => 'wh_1'])
    ->stubError('payments', status: 400, body: ['errors' => [['code' => 'invalid_value']]])
    ->stubException('payments/*', new \Illuminate\Http\Client\ConnectionException('timeout'));
```

Closure stubs receive `(Illuminate\Http\Client\Request $request, array $options)` from Laravel's HTTP client and may return a `Response`, a `PromiseInterface`, or any value `Http::response()` accepts. The `$options` parameter can be ignored when not needed:

```php
$asaas->stub('payments/*', function (\Illuminate\Http\Client\Request $r): \GuzzleHttp\Promise\PromiseInterface {
    return Http::response(['id' => 'pay_'.uniqid()], 201);
});
```

Pass response headers via the fourth `stubError()` parameter when simulating retry/throttling responses:

```php
$asaas->stubError('payments', status: 429, body: [], headers: ['Retry-After' => '30']);
```

### Path patterns

Stub paths are **relative** to the API base URL — no `https://...`, no `/v3` prefix. Patterns use `Str::is` semantics where `*` is a glob.

A trailing `*` is appended automatically when the pattern doesn't already end in one, so path-only patterns also match URLs that carry query strings or extra segments. `'payments'` matches `/payments`, `/payments?limit=10`, `/payments/pay_123`. Use an explicit suffix when you need a tighter match — e.g. `'payments/*'` matches only individual-payment paths, never the index endpoint.

Patterns are **prefix-globs, not segment-bounded**: `'payments'` also matches sibling-prefix paths like `/paymentsBook` if such an endpoint existed. Stick to either explicit segments (`'payments/*'`) or full paths when adjacent endpoints share a prefix.

When multiple stubs match the same URL, the **first registered wins**. Construct ordering matters: list specific stubs before broader ones if both could match.

### Pagination

`['data' => [...]]` infers `hasMore=false` and `totalCount=count($data)` automatically (and fills sensible defaults for `object`, `limit`, `offset`). Providing either `hasMore` or `totalCount` disables inference entirely — supply all paging fields you care about yourself in that case. To drive multi-page flows through `->all()`:

```php
$asaas->stubPages('payments', [
    ['data' => [['id' => 'a']], 'hasMore' => true,  'totalCount' => 2],
    ['data' => [['id' => 'b']], 'hasMore' => false, 'totalCount' => 2],
]);
```

### Assertions

```php
use Illuminate\Http\Client\Request;

$asaas->assertSent('payments', fn (Request $r) => $r['value'] === 100.0);
$asaas->assertSent('payments/*', times: 2);     // also: exact-N count for a pattern
$asaas->assertNotSent('webhooks/*');
$asaas->assertSentCount(3);                      // total across all patterns
$asaas->assertNothingSent();

$asaas->assertSentInOrder([
    'accounts',
    'accounts/*',
    'accounts/*/accessTokens',
]);

// Inspect raw recordings.
$asaas->recorded();             // list<array{Request, Response}>
$asaas->recorded('payments');
```

Recordings accumulate for the lifetime of the fake — there is no `flush()` or `forget()`. Build a fresh `AsaasClient::fake()` per test (the recommended pattern) to start with a clean slate.

### Loud catch-all

Unmatched requests throw `OwnerPro\Asaas\Testing\NoMatchingStubException` with the request method, URL, and the list of registered patterns. This catches forgotten stubs early instead of letting tests pass against empty 200 responses.

### Laravel container

The service provider binds the real `AsaasClient` as a singleton and registers an alias so `AsaasClientContract` resolves to the same instance. Type-hint application code against `AsaasClientContract` to make the seam swappable:

```php
public function __construct(private readonly AsaasClientContract $asaas) {}
```

Swap the fake in tests by overriding the contract binding:

```php
$this->app->instance(\OwnerPro\Asaas\Contracts\AsaasClientContract::class, AsaasClient::fake([...]));
```

`FakeAsaasClient` does **not** extend `AsaasClient` (the production class is `final`), so code that type-hints the concrete `AsaasClient` cannot receive the fake from the container. Migrate those constructor signatures to `AsaasClientContract` to make them testable.

## License

MIT
