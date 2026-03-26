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
Asaas::payments()->refund(string $id, array $data = []): AsaasResult
Asaas::payments()->restore(string $id): AsaasResult
Asaas::payments()->captureAuthorized(string $id): AsaasResult
Asaas::payments()->payWithCreditCard(string $id, array $data): AsaasResult
Asaas::payments()->receiveInCash(string $id, array $data = []): AsaasResult
Asaas::payments()->undoReceivedInCash(string $id): AsaasResult
Asaas::payments()->status(string $id): AsaasResult
Asaas::payments()->billingInfo(string $id): AsaasResult
Asaas::payments()->pixQrCode(string $id): AsaasResult
Asaas::payments()->identificationField(string $id): AsaasResult
Asaas::payments()->viewingInfo(string $id): AsaasResult
Asaas::payments()->simulate(array $data): AsaasResult
Asaas::payments()->limits(): AsaasResult
Asaas::payments()->all(array $filters = []): Generator
```

### Pix Keys (`pix()`)

```php
Asaas::pix()->createKey(array|CreatePixKeyRequest $data): AsaasResult
Asaas::pix()->findKey(string $id): AsaasResult
Asaas::pix()->listKeys(array $query = []): AsaasPaginatedResult
Asaas::pix()->deleteKey(string $id): AsaasResult
Asaas::pix()->createStaticQrCode(array $data = []): AsaasResult
Asaas::pix()->deleteStaticQrCode(string $id): AsaasResult
Asaas::pix()->tokenBucket(): AsaasResult
Asaas::pix()->all(array $filters = []): Generator
```

### Pix Transactions (`pixTransactions()`)

```php
Asaas::pixTransactions()->decodeQrCode(array $data): AsaasResult
Asaas::pixTransactions()->payQrCode(array $data): AsaasResult
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
Asaas::billPayments()->simulate(array $data = []): AsaasResult
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
