# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-07-20

Opt-in typed transport exceptions for timeout reconciliation (PulsarApi #463).
No breaking changes — with the flag off (default), behavior is byte-identical
to 2.0.0.

> **Highly recommended: enable `throw_on_transport_failure`.** The legacy
> `CONNECTION_ERROR` default exists only for backward compatibility and will
> likely become the default in a future major. Without the flag, a transport
> failure is indistinguishable — by type — from a definitive API rejection,
> which invites blind retries of operations that may have moved money.

### Added

- **`throwOnTransportFailure` flag** on `AsaasClient::for()`, `Asaas::for()`,
  `AsaasConnector::forStandalone()/forLaravel()` and the
  `asaas.throw_on_transport_failure` config key (env
  `ASAAS_THROW_ON_TRANSPORT_FAILURE`, default `false`). When enabled, the
  swallow-into-`CONNECTION_ERROR` path no longer exists; transport failures
  throw one of two typed exceptions:
  - `OwnerPro\Asaas\Support\RequestNotDeliveredException`
    (`phase: 'connect'|'dns'|'tls'`) — the request provably never reached the
    API (cURL 6/7/35/58/60); a direct retry is safe. Timeouts (cURL 28) are
    never classified here: reused keep-alive connections report zeroed
    connection timers, so a connect-phase timeout cannot be proven.
  - `OwnerPro\Asaas\Support\IndeterminateResultException`
    (`phase: 'body'|'read'|'transfer'|null`) — the API may or may not have
    processed the request (read timeout, connection lost mid-transfer, 2xx
    whose body is not a JSON object/array); never retry blindly — reconcile
    first.
  Classification biases toward indeterminate: any ambiguity (unknown errno,
  missing handler context) classifies as `IndeterminateResultException` with
  `phase: null`. The original `Illuminate\Http\Client\ConnectionException`
  is preserved in `getPrevious()`. With the flag on, a 2xx response whose
  body is not a JSON object/array throws (`phase: 'body'`) instead of
  silently succeeding with empty `data` — except 204 No Content, which stays
  a success. Definitive HTTP errors (4xx/5xx) still return `AsaasResult`
  failures in both modes.
- **Exception hierarchy**: new `OwnerPro\Asaas\Support\AsaasException` base
  and abstract `TransportException` (parent of both typed exceptions).
  `AsaasRequestException` now extends `AsaasException` (previously
  `RuntimeException` directly — non-breaking, it still is one transitively).
- **Transport failure fakes**: `AsaasClient::fake()` accepts
  `throwOnTransportFailure:`; `FakeAsaasClient` gains
  `stubRequestNotDelivered(pattern, phase)` and
  `stubIndeterminateResult(pattern, phase)`. Stubs build production-shaped
  exception chains (Guzzle `ConnectException` with real cURL errnos) that
  flow through the same classifier as live traffic, so they honour the flag
  in both modes.

### Changed

- `AsaasConnector::__construct()` gained an optional third parameter
  (`throwOnTransportFailure = false`) and is now annotated `@internal` —
  prefer constructing via `forStandalone()`/`forLaravel()`. Direct 2.0-style
  construction keeps working unchanged.

### Deprecated

- The `CONNECTION_ERROR` result path (`statusCode 0`, `errors[0]['code'] ===
  'CONNECTION_ERROR'`) is deprecated in favour of the typed exceptions. It
  remains the default; a future major may flip the default.

## [2.0.0] - 2026-05-13

Major release. Two consecutive spec-alignment audits against `specs/asaas_openapi.json`:

- **First pass** — closed 19 documented field gaps, the wrong-verb bug on `TransferResource::cancel()`, the `accessTokenConfig` / `permissions` payload pieces (without which subaccounts created via the SDK inherited a key with no `TRANSFER` permission and blocked the production flow), and added 27 new endpoints across 9 domains (fiscal info, payment documents, escrow, payment checkout personalisation, wallets, lean payments, split lookup).
- **Second pass** — triggered by a client-reported production bug (`accounts()->updateAccessToken()` accepting bodies without `name`/`enabled`/`expirationDate`), a 14-dimension audit closed 15 further gaps across enum coverage, required-ness, cross-field validation, endpoint parity, and 204 handling.

### Breaking

- **Dropped Laravel 11 support; added Laravel 13 support.** `composer.json`
  now requires `illuminate/http` / `illuminate/support` `^12.0|^13.0` (was
  `^11.0|^12.0`). The CI matrix tests Laravel 12 and 13 against PHP 8.3 and
  8.4. Reason: Laravel 11's `Http\Client\PendingRequest::parseMultipartBodyFormat`
  does not flatten nested arrays into `key[]` multipart elements — it passes
  the raw nested array straight to Guzzle, which then throws
  `InvalidArgumentException: A 'contents' key is required`. Laravel 12
  introduced the flattening behavior the SDK's multipart payload encoding now
  depends on. Rather than re-implement the flattening inside `MultipartPayload`
  for an EOL-imminent framework, the constraint was tightened. Migration:
  upgrade to Laravel 12 or 13.
- `OwnerPro\Asaas\Account\DocumentType` enum reworked to match the 12 KYC
  document types Asaas accepts. The previous `Minutes = 'MINUTES'` case was
  rejected by the server (server expects `MINUTES_OF_CONSTITUTION` /
  `MINUTES_OF_ELECTION`); seven legitimate cases were missing entirely
  (`ALLOW_BANK_ACCOUNT_DEPOSIT_STATEMENT`, `EMANCIPATION_OF_MINORS`,
  `IDENTIFICATION_SELFIE`, `INVOICE`, `MEI_CERTIFICATE`, `POWER_OF_ATTORNEY`,
  plus the two split-minutes cases). Migration: replace `DocumentType::Minutes`
  with `DocumentType::MinutesOfConstitution` or `MinutesOfElection` depending
  on the document. New cases unblock the white-label subaccount onboarding
  flow for the 8 KYC types previously inaccessible via typed enum.
- `OwnerPro\Asaas\Account\Request\AccessTokenRequest` deleted. Split into two
  DTOs reflecting the spec's per-verb required-ness:
  - `CreateAccessTokenRequest` (POST `/accounts/{id}/accessTokens`) — every
    field optional; matches Asaas's default-application semantics on create.
  - `UpdateAccessTokenRequest` (PUT `/accounts/{id}/accessTokens/{tokenId}`)
    — `name`, `enabled`, `expirationDate` are mandatory positional
    constructor arguments; `fromArray()` throws `InvalidArgumentException`
    when any of the three is absent.

  Migration:
  ```php
  // Before
  new AccessTokenRequest(name: 'x', expirationDate: '...')
  // After (POST)
  new CreateAccessTokenRequest(name: 'x', expirationDate: '...')
  // After (PUT)
  new UpdateAccessTokenRequest(name: 'x', enabled: true, expirationDate: '...')
  ```
- `OwnerPro\Asaas\Account\Request\CommercialInfoRequest::$name` and `$tradingName`
  removed. Both are response-only per Asaas docs (`POST /v3/myAccount/commercialInfo`
  request schema lists 15 fields, neither among them) — keeping them on the DTO
  promised an API that does not exist; Asaas silently dropped the keys on the
  wire. Migration: drop these arguments from `new CommercialInfoRequest(...)`
  calls. `name` and `tradingName` are populated by Asaas server-side and surface
  only on response payloads.
- `OwnerPro\Asaas\Account\Request\AccountRequest::$tradingName` removed. Same
  story: response-only ("preenchido automaticamente" in the spec response
  schemas), absent from the `POST /v3/accounts` request body documented at
  https://docs.asaas.com/reference/criar-subconta. Migration: drop the argument
  from `new AccountRequest(...)` / `fromArray(['tradingName' => ...])` callers.
- `OwnerPro\Asaas\Transfer\Request\TransferRequest::$walletId` removed. The
  field belongs to the **internal-transfer** endpoint
  (`POST /v3/transfers/`, trailing slash) and was leaking into the public
  `POST /v3/transfers` body as a legacy backward-compat affordance. The
  Asaas-documented body for `POST /v3/transfers`
  (https://docs.asaas.com/reference/transferir-para-conta-de-outra-instituicao-ou-chave-pix)
  does not list `walletId`. Migration: route Asaas-to-Asaas transfers through
  `transfers()->createInternal(new InternalTransferRequest(value: ..., walletId: ...))`,
  which is the canonical endpoint for wallet-to-wallet movement.
- `TransferResource::cancel($id)` now sends **DELETE** (was POST). Asaas's spec requires DELETE on `/v3/transfers/{id}/cancel` — POST silently failed or hit the wrong handler in some configurations. Any consumer wrapping the SDK's HTTP layer (e.g. retry middleware keyed by method) must update accordingly.
- `PayWithCreditCardRequest::$creditCard`, `$creditCardHolderInfo`, and `$remoteIp` are now optional (`?CreditCard`, `?CreditCardHolderInfo`, `?string`) to support the new token-only flow. Constructors using positional args keep working; consumers relying on the previous "throws when missing" semantics will no longer see those exceptions and must validate at their own boundaries.
- `Connector::postMultipart()` no longer throws on an empty `$files` array — `$files` is now optional with a `[]` default. Custom `Connector` implementations must update their signature to match (`array $files = []`). The change unblocks form-only multipart endpoints (`/v3/fiscalInfo/`, `/v3/myAccount/paymentCheckoutConfig/`) where the binary file is optional. The previous "at least one file" invariant was an artificial guard, not a protocol requirement.
- `OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest::$email` and
  `$apiVersion` removed. Both are accepted on `POST /v3/webhooks` (create) but
  the documented `PUT /v3/webhooks/{id}` body
  (https://docs.asaas.com/reference/atualizar-webhook-existente) lists only
  `name`, `url`, `sendType`, `enabled`, `interrupted`, `authToken`, `events`.
  The SDK contract follows the published spec + docs, not observed runtime
  acceptance — Asaas can tighten validation at any time. Migration: drop
  the arguments from `new UpdateWebhookRequest(...)` / `fromArray()` callers.
  To change a webhook's notification email or API version, delete the webhook
  and recreate it via `webhooks()->create()`.
- `MyAccountResource::updateDocumentFile()` — `DocumentType|string $type`
  argument removed. `POST /v3/myAccount/documents/files/{id}` (spec +
  https://docs.asaas.com/reference/atualizar-documento-enviado) accepts only
  `documentFile`; Asaas keeps the document `type` slot fixed on update. The
  SDK no longer forwards a `type` form-data part. Migration: drop the `type:`
  argument from `myAccount()->updateDocumentFile(...)` calls. To re-categorise
  a document, delete the file and re-upload via `uploadDocumentFile()`.
- `AccountResource::findAccessToken()` removed. `GET /v3/accounts/{id}/accessTokens/{accessTokenId}`
  is not documented by Asaas — the
  [subaccount API key management guide](https://docs.asaas.com/docs/gerenciamento-de-chaves-de-api-de-subcontas)
  exposes only list, create, update and delete; single-token retrieve was
  inferred by REST symmetry and accepted at runtime, never by contract.
  Migration: call `accounts()->listAccessTokens($accountId)` and filter by
  `id`, or retain the `accessTokenId` returned by `createAccessToken()`.
- `MyAccountResource::bankAccount()` and `MyAccountResource::updateBankAccount()`
  removed, along with the `AccountBankAccountRequest` DTO. `GET` and `POST` on
  `/v3/myAccount/bankAccountInfo` are not documented anywhere on Asaas's public
  docs — the field appears only as a status flag (`PENDING`/`APPROVED`/`REJECTED`)
  inside `GET /v3/myAccount/status`, and the
  [subaccount approval flow guide](https://docs.asaas.com/docs/detalhamento-do-fluxo-de-aprova%C3%A7%C3%A3o-de-subcontas)
  enumerates the endpoints used during onboarding without naming a bank-account
  CRUD pair. Both methods were added by runtime-observation to support the
  white-label onboarding flow, not from any documented contract. Migration:
  drive the bank-account registration through the Asaas web panel; monitor
  `myAccount()->status()['bankAccountInfo']` to track approval.

### Added — DTO fields

- `CreatePaymentRequest`: `daysAfterDueDateToRegistrationCancellation`, `installmentCount`, `installmentValue`, `totalValue`, `pixAutomaticAuthorizationId`, and `creditCardToken` (`?string`) — the saved-card-token mode on `POST /v3/payments/`. Send a previously stored card token in the same request that creates the payment, without resubmitting card and holder data.
- `UpdatePaymentRequest`: `daysAfterDueDateToRegistrationCancellation`, `callback`.
- `PayWithCreditCardRequest`: `creditCardToken` (lets you pay using a saved-card token without sending card details again).
- `AccountRequest`: `loginEmail`, `webhooks` (list of `CreateWebhookRequest`; coerced from raw arrays), and `accessTokenConfig` (`{name, permissions[]}`) — set the initial subaccount API key's name and permission scope at creation time so the key ships ready for `TRANSFER`, `PIX_*`, `WEBHOOK`, etc. without a manual visit to the painel. If omitted, Asaas's default (all permissions in `READ_WRITE`) still applies.
- `UpdateAccessTokenRequest`: `permissions` (`list<AccessTokenPermissionConfig>`) — same shape, accepted by `accounts()->updateAccessToken(...)` for adjusting an existing key's permissions.
- `CommercialInfoRequest`: `personType` (uses existing `OwnerPro\Asaas\Account\PersonType` enum), `companyName`.
- `CreateInvoiceRequest`, `UpdateInvoiceRequest`: `updatePayment` (auto-discount taxes from the payment value).
- `CreateBillPaymentRequest`: `value` (required for credit-card bills whose digitable line carries no embedded amount).
- `TransferRequest`: `recurring` (Pix-recurrence object; nested `Recurring` value object with `frequency` enum + `quantity`).

### Added — value objects + enums

- `OwnerPro\Asaas\Support\DTO\Discount` (`{value, dueDateLimitDays, type}`), `Interest` (`{value}`), `Fine` (`{value, type}`). Each ships a static `coerce()` helper that accepts a float (legacy shape, wrapped as `value`), a raw array, an instance, `Missing::Value`, or `null`. The Payment Create/Update DTOs now hold typed instances on `$discount`, `$interest`, `$fine` (previously `?float` — see Migration below).
- `OwnerPro\Asaas\Payment\DiscountType` (`FIXED`, `PERCENTAGE`).
- `OwnerPro\Asaas\Payment\FineType` (`FIXED`, `PERCENTAGE`).
- `OwnerPro\Asaas\Transfer\Request\Recurring` value object + `OwnerPro\Asaas\Transfer\TransferRecurrenceFrequency` enum (`WEEKLY`, `MONTHLY`).
- `OwnerPro\Asaas\Transfer\Request\InternalTransferRequest` (for the `POST /v3/transfers/` internal-transfer endpoint).
- `OwnerPro\Asaas\Support\DTO\Callback` gains a `coerce()` static helper consistent with the other value objects.
- `BillingType` enum gains `MUNDIPAGG_CIELO`, `VOUCHER_CARD`, `ASAAS_MONEY` (still string-pass-through, but typed callers can now use the cases).
- `OwnerPro\Asaas\Account\AccessTokenPermission` enum — all 33 documented permission codes (`PAYMENT`, `TRANSFER`, `WEBHOOK`, `PIX_*`, `INVOICE`, `BILL`, …).
- `OwnerPro\Asaas\Account\AccessTokenScope` enum (`READ`, `READ_WRITE`).
- `OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig` — `{name, scope}` pair; the DTO that goes inside `permissions[]`.
- `OwnerPro\Asaas\Account\Request\AccessTokenConfig` — `{name, permissions[]}`; the object form Asaas expects under `accessTokenConfig`.

### Added — resource endpoints

- `PaymentResource::createWithCreditCard(array|CreatePaymentRequest $data)` — `POST /v3/payments/` (trailing slash) for the one-shot create-with-card flow.
- `PaymentResource::listRefunds(string $id)` — `GET /v3/payments/{id}/refunds`.
- `PaymentResource::refundBankSlip(string $id)` — `POST /v3/payments/{id}/bankSlip/refund`.
- `PaymentResource::getChargeback(string $id)` — `GET /v3/payments/{id}/chargeback`.
- `PaymentResource::getEscrow(string $id)` — `GET /v3/payments/{id}/escrow`.
- `PaymentResource::finishEscrow(string $id)` — `POST /v3/escrow/{id}/finish` to release escrow on a payment.
- `PaymentResource::uploadDocument(...)`, `listDocuments(string $paymentId)`, `findDocument(string $paymentId, string $documentId)`, `updateDocument(string $paymentId, string $documentId, array|UpdatePaymentDocumentRequest $data)`, `deleteDocument(string $paymentId, string $documentId)` — full CRUD for `/v3/payments/{id}/documents/*` (upload is multipart with `type`, `availableAfterPayment`, and the file).
- `PaymentResource::listSplitsPaid(array $query = [])`, `findSplitPaid(string $id)`, `listSplitsReceived(array $query = [])`, `findSplitReceived(string $id)` — the four `/v3/payments/splits/(paid|received)/*` endpoints.
- `LeanPaymentResource::list()`, `LeanPaymentResource::update()`, `LeanPaymentResource::delete()`, `LeanPaymentResource::all()` — closes the CRUD parity gap with `PaymentResource` for `/v3/lean/payments`.
- `AccountResource::createAccessToken($accountId, $data = null)` now accepts an optional `CreateAccessTokenRequest` (or array) body with `name`/`expirationDate`.
- `AccountResource::escrowConfig(string $accountId)`, `setEscrowConfig(string $accountId, array|EscrowConfigRequest $data)`, `defaultEscrowConfig()`, `setDefaultEscrowConfig(array|EscrowConfigRequest $data)` — manage escrow accounts at both the per-subaccount level (`/v3/accounts/{id}/escrow`) and the default-for-all level (`/v3/accounts/escrow`).
- `MyAccountResource::accountNumber()` — `GET /v3/myAccount/accountNumber`.
- `MyAccountResource::fees()` — `GET /v3/myAccount/fees/`.
- `MyAccountResource::paymentCheckoutConfig()` and `updatePaymentCheckoutConfig(array|PaymentCheckoutConfigRequest $data, mixed $logoFile = null, ?string $logoFilename = null)` — GET / POST `/v3/myAccount/paymentCheckoutConfig/`. The save call is multipart and accepts an optional logo binary.
- `MyAccountResource::wallets(array $query = [])` — `GET /v3/wallets/`, the walletId listing.
- `MyAccountResource::findDocumentFile(string $fileId)` and `MyAccountResource::updateDocumentFile(string $fileId, mixed $file, string $filename)` — completes the `/v3/myAccount/documents/files/{id}` triplet (GET / POST / DELETE). Asaas keeps the document `type` slot fixed on update, so the SDK forwards only the `documentFile` multipart part (matching the spec and https://docs.asaas.com/reference/atualizar-documento-enviado).
- `MyAccountResource::approveSandbox()` — wraps the sandbox-only `POST /v3/sandbox/myAccount/approve` endpoint, fast-approving every status slot (commercial info, bank account, documentation, general) to unblock white-label onboarding integration tests. Returns HTTP 400 in production; only call against `Environment::Sandbox`.
- `StatementResource::balance()` — `GET /v3/finance/balance`.
- `StatementResource::paymentStatistics(array $query = [])` — `GET /v3/finance/payment/statistics`.
- `StatementResource::splitStatistics(array $query = [])` — `GET /v3/finance/split/statistics`.
- `TransferResource::createInternal(array|InternalTransferRequest $data)` — `POST /v3/transfers/` (trailing slash) for the internal-Asaas-account flow with `walletId`.

### Added — new resources

- `OwnerPro\Asaas\FiscalInfo\FiscalInfoResource` (resolved via `Asaas::fiscalInfo()` / `$client->fiscalInfo()`) covering `/v3/fiscalInfo/*`: `recover()`, `save(array|FiscalInfoRequest $data, mixed $certificateFile = null, ?string $certificateFilename = null)` (multipart, optional A1 certificate), `municipalOptions()`, `services()`, `federalServiceCodes()`, `nbsCodes()`, `operationIndicatorCodes()`, `taxClassificationCodes()`, `taxSituationCodes()`, and `configureNationalPortal(bool $enabled)`.
- `OwnerPro\Asaas\Payment\LeanPaymentResource` (resolved via `Asaas::leanPayments()` / `$client->leanPayments()`) covering `/v3/lean/payments/*` — slim-response variants of the standard payment endpoints: `create()`, `createWithCreditCard()`, `find()`, `captureAuthorized()`, `restore()`, `refund()`, `receiveInCash()`, `undoReceivedInCash()`, plus the CRUD parity additions (`list()`, `update()`, `delete()`, `all()`) listed above. Reuses the same request DTOs as `PaymentResource`.

### Added — DTOs and enums

- `OwnerPro\Asaas\FiscalInfo\Request\FiscalInfoRequest` — partial-update DTO for the `POST /v3/fiscalInfo/` body (every form field except the binary).
- `OwnerPro\Asaas\Payment\PaymentDocumentType` enum (`INVOICE`, `CONTRACT`, `MEDIA`, `DOCUMENT`, `SPREADSHEET`, `PROGRAM`, `OTHER`).
- `OwnerPro\Asaas\Payment\Request\UpdatePaymentDocumentRequest` — required-fields DTO for `PUT /v3/payments/{id}/documents/{documentId}`.
- `OwnerPro\Asaas\Account\Request\EscrowConfigRequest` — `{daysToExpire, enabled?, isFeePayer?}` for the four escrow-config endpoints.
- `OwnerPro\Asaas\Account\Request\PaymentCheckoutConfigRequest` — `{logoBackgroundColor, infoBackgroundColor, fontColor, enabled?}` for the checkout-personalisation save.

### Changed

- `AsaasConnector::__construct(PendingRequest $pendingRequest, string $baseUrl)`
  — the second argument is now **required** (was `string $baseUrl = ''`).
  Removed to eliminate an unkillable mutation: no test could distinguish
  the default from an explicit `''` because every documented construction
  path supplies a real URL. All factories (`forStandalone`, `forLaravel`)
  and `FakeAsaasClient` already pass it, so users going through
  `AsaasClient::for()`, the `Asaas` facade, or the Laravel service
  provider are unaffected. Only code calling `new AsaasConnector($pr)`
  directly (not documented in the README, no in-repo callers) needs to
  add an explicit baseUrl — pass `''` to keep the prior behaviour, or
  the real `Environment::baseUrl()` if the `PendingRequest` is not
  already pre-configured.

### Fixed

- `PayWithCreditCardRequest` now validates cross-field in the constructor and
  `fromArray()`: rejects payloads where neither `creditCardToken` nor both
  `creditCard` and `creditCardHolderInfo` are present. Throws
  `InvalidArgumentException` synchronously instead of failing on the server.
- `PaymentResource::createWithCreditCard()` guards `remoteIp` (required by
  Asaas antifraud analysis on `/v3/payments/`) and the same token-vs-card
  cross-field rule before the HTTP roundtrip.
- `LeanPaymentResource::createWithCreditCard()` mirrors the
  `PaymentResource::createWithCreditCard()` guard for `/v3/lean/payments/`:
  throws `InvalidArgumentException` synchronously when `remoteIp` is absent
  or when neither `creditCardToken` nor both `creditCard` and
  `creditCardHolderInfo` are present. Previously the Lean flow forwarded the
  payload straight to Asaas and surfaced a generic 400 to the caller.
- `AsaasConnector::extractErrors()` no longer returns an empty `errors`
  list when the upstream envelope is `{ "errors": [] }` (or `errors`
  missing) on a 4xx/5xx response. The SDK now synthesizes
  `[['code' => 'UNKNOWN_ERROR', 'description' => 'Asaas returned empty
  errors array (status {status})']]`, so `$result->errors[0]` is always
  populated and `$result->orFail()` surfaces a useful message instead of
  the generic `Asaas API error` fallback from `AsaasRequestException`.
- `InvoiceResource::cancel()` accepts an optional
  `array|CancelInvoiceRequest|null` body so callers can pass
  `cancelOnlyOnAsaas` to `POST /v3/invoices/{id}/cancel`. Previously the
  SDK sent an empty body, dropping the flag and forcing every cancellation
  through the prefeitura. The new `CancelInvoiceRequest` DTO uses the
  `Missing` pattern so the field is omitted unless the caller opts in,
  preserving Asaas's server-side default when no DTO is supplied.
- `UpdateInvoiceRequest`, `UpdatePaymentRequest`, and `UpdateWebhookRequest`
  no longer accept explicit `null` for their fields. Property types tightened
  from `T|Missing|null` to `T|Missing`, and the `coerce()` helpers on
  `Callback`, `Discount`, `Fine`, `Interest` follow suit (`self|Missing`,
  drop `null`). The Asaas OpenAPI spec marks every request-body field as
  `nullable: false`, so the previous null-pass-through was always rejected
  by the server with HTTP 400 (or, worse, silently cleared the field on
  certain endpoints). Migration: callers that passed `null` hoping to send
  `{"field": null}` must now omit the field instead — either skip the
  constructor argument (it defaults to `Missing::Value`) or drop the key
  from the array passed to `fromArray()` / the resource method. Passing
  `null` to a typed field now raises `TypeError` at construction time.

### Migration notes

- `discount` / `interest` / `fine` on `CreatePaymentRequest` and `UpdatePaymentRequest`: passing a float keeps working — the SDK wraps it as `Discount(value: $float)` / `Interest(...)` / `Fine(...)`. Wire output is now the documented object shape (`{value: ..., dueDateLimitDays: ..., type: ...}`), which is what Asaas's validator describes. If you were relying on the older scalar-on-wire behavior, audit your downstream consumers accordingly.
- `PayWithCreditCardRequest::fromArray` no longer throws on missing `creditCard`, `creditCardHolderInfo`, or `remoteIp` — required validation moves to the server. Provide either `creditCardToken` **or** the full card/holder/IP triple.
- `TransferResource::cancel()`: confirmed via the audit that the spec uses DELETE. If you reverse-proxy or log by HTTP method, update mappings.
- Custom `Connector` implementations: the `postMultipart()` interface signature changed from `(string, array, array)` to `(string, array, array = [])`. Native PHP signatures must include the default value to remain LSP-compatible.

### Internal

- `Discount`, `Interest`, `Fine`, `Callback` each expose a static `coerce()` helper, normalising union inputs (`array | float | DTO | Missing | null`) into a normalized DTO instance. This kept `UpdatePaymentRequest`'s constructor cognitive complexity under PHPStan's class threshold without weakening the public API.
- Wire-level integration tests added to `AccountResourceTest` pinning that `POST /v3/accounts` carries `accessTokenConfig` end-to-end (both via raw array and via `AccessTokenConfig` DTO with enum cases), and that `PUT /v3/accounts/{id}/accessTokens/{tokenId}` carries `permissions` in the documented `{name, scope}` shape. Closes the e2e coverage gap on the feature that motivated the first audit pass (subaccount keys ship with `TRANSFER` permission so `POST /transfers` no longer blocks production).

### Documentation

- README — DocumentType enum table updated to list all 12 KYC types.
- README — new "Date formats" section enumerating which DTO fields expect
  `YYYY-MM-DD` vs `YYYY-MM-DD HH:MM:SS`, since the SDK passes strings through
  verbatim and Asaas rejects `T`/`Z`/timezone offsets.
- README — new "Available filters per list endpoint" section with one table
  per `list()` method (`payments`, `accounts`, `invoices`, `transfers`,
  `pix`, `pixTransactions`, `pixAutomatic`, `statements`).
- README — `AccessTokenRequest` migration note and updated subaccount
  onboarding example using `UpdateAccessTokenRequest`.
- README — note that `accounts()->deleteAccessToken()` and
  `webhooks()->removeBackoff()` return HTTP 204 with an empty body; check
  `$result->success`, not `$result->data`.
- README — `creditCardToken` example for `payments()->createWithCreditCard()`.
- README — documented the `Discount` / `Interest` / `Fine` value objects in the nested-DTOs table and added a usage example showing both legacy float and typed-DTO forms — the property type change (from `?float` to `?Discount|?Interest|?Fine`) is the load-bearing breaking change of this release.
- README — updated the Custom Connector example to match the new `postMultipart(string, array, array = [])` signature.
- PHPDoc — `BillingType` enum docblock split request-acceptable cases
  (`Undefined`, `Boleto`, `CreditCard`, `Pix`) from response-only cases
  (`DebitCard`, `Transfer`, `Deposit`, `MundipaggCielo`, `VoucherCard`,
  `AsaasMoney`) that Asaas only returns on response bodies.
- PHPDoc — date format strings on `CreatePaymentRequest::$dueDate`,
  `StaticQrCodeRequest::$expirationDate`, `AuthorizationRequest::$startDate`/
  `$finishDate`/`$contractId`/`$description`.
- PHPDoc — maxLength constraints on `StaticQrCodeRequest::$externalReference`
  (100), `AuthorizationRequest::$contractId`/`$description` (35) as Asaas
  validates server-side.
- PHPDoc — partial-update semantics documented on `FiscalInfoRequest` and
  `UpdatePaymentRequest`.
- PHPDoc + README — server-side defaults for `FiscalInfoRequest`
  (`simplesNacional=true`, `culturalProjectsPromoter=true`): the SDK omits the
  field on partial updates so a re-save never silently overwrites the
  consumer's previous choice. Pin at the call site if you need stability
  across Asaas-side default changes.
- Wire tests pin all 12 `DocumentType` cases on the multipart body of
  `MyAccountResource::uploadDocumentFile()` and the 204 No Content path on
  both `deleteAccessToken` and `removeBackoff`.
- PHPDoc + README — spec-mirroring P3 doc batch closing the residual gaps
  surfaced by the 16-dimension audit. No behaviour change: docblocks on
  `BankAccount::$ownerBirthDate` (date format), `PayWithCreditCardRequest`
  (cross-field token-vs-card rule, less strict than the spec),
  `CreateWebhookRequest` (`url`/`email` promoted to required as a safety net
  beyond the spec), `AccessTokenConfig` (inline-only convenience used by
  `AccountRequest`), `MyAccountResource::uploadDocumentFile()` and
  `updateDocumentFile()` (binary file contents wording aligned with the
  other multipart methods), `MyAccountResource::bankAccount()` /
  `updateBankAccount()` (extra-spec but accepted by Asaas in production),
  `AccountResource::findAccessToken()` and the escrow-config block
  (clarifying extra-spec and cross-domain placement, both candidates for
  dedicated Resources in a future major), `HasUpdatableArrayFactory` and
  `Missing` (codifying the `T|Missing` typing rule that prevents the
  null-leak class of bugs surfaced by the second audit pass),
  `Statement\FinancialTransactionType` and `Transfer\TransferOperationType`
  (response-classification helpers, with `Internal` flagged as response-only
  on `TransferOperationType`), and `AsaasConnector::extractErrors()`
  (best-effort normalization contract).
  README — short note above the Resources section explaining that
  `AsaasResult::$data` stays `array<string, mixed>` (consult the spec /
  Asaas docs for response field shape), and a "Out of scope" admonition
  on `myAccount()` covering the sandbox-only approve endpoint and the
  extra-spec `bankAccountInfo` pair.

## [1.4.0] - 2026-05-12

### Added

- `CreateWebhookRequest::interrupted` and `UpdateWebhookRequest::interrupted` — webhook sync-queue interruption flag from the Asaas OpenAPI spec. The Asaas validator rejects webhook creation with HTTP 400 (`"O parâmetro poolInterrupted deve ser informado"` — `poolInterrupted` is the validator's internal variable name; the wire field is `interrupted`) whenever the field is absent, so `CreateWebhookRequest::interrupted` defaults to `false` to keep the request acceptable out of the box. `UpdateWebhookRequest::interrupted` uses the standard `Missing::Value` partial-update default.

### Fixed

- `WebhookResourceTest` now asserts request body shape (not only URL/method), so missing fields on webhook DTOs fail loudly going forward.

## [1.3.0] - 2026-05-08

### Added

- `OwnerPro\Asaas\Contracts\AsaasClientContract` — production interface; `AsaasClient` and `FakeAsaasClient` both implement it for swappable seams.
- `AsaasClient::fake()` — first-class test helper. Constructor or fluent API (`stub`, `stubError`, `stubException`, `stubPages`).
- Catch-all: unmatched fake requests throw `OwnerPro\Asaas\Testing\NoMatchingStubException` listing every registered pattern.
- Pagination inference for stubs of shape `['data' => [...]]` (auto-fills `hasMore=false`, `totalCount=count($data)`).
- Assertions: `assertSent` (pattern + callback + `times`), `assertNotSent`, `assertSentCount`, `assertNothingSent`, `assertSentInOrder` (sequential flows with allowed interleaving).
- `recorded()` and `recorded(pattern)` to inspect captured `(Request, Response)` pairs.
- `AsaasServiceProvider` aliases `AsaasClientContract` to the bound `AsaasClient` singleton, so the contract is resolvable out of the box.

### Migration notes

- The legacy "Custom Connector" pattern (hand-rolled `FakeConnector implements Connector`) still works — no breaking change. New tests should prefer `AsaasClient::fake()` for richer assertions, recording, and the loud catch-all.
- Application code that injects the concrete `AsaasClient` cannot receive `FakeAsaasClient` via the container (`final class`). Switch those constructors to `AsaasClientContract` to make them swappable.

## [1.2.1] - 2026-05-07

### Fixed

- Plug mutation-test escapes that the `pest-plugin-mutate` cache was hiding locally. Required a stricter assertion on the KYC document upload (asserts the `IDENTIFICATION` type marker and `filename="rg.png"` actually land in the multipart body), positive predicates on the `restores JSON body format` tests (the previous `assertSent` callback early-returned `true` on the unrelated upload request, so the assertion passed even when `asJson()` was removed), a new test that forwards per-file headers into the multipart attachment, and dropping the unreachable `cpfCnpj instanceof Missing` ternary inside `CommercialInfoRequest::__debugInfo()` (the earlier `array_filter` already strips Missing values, so the branch was dead code).
- Drop the dead default suffix on `PixTransactionResource::recurringItemPath()` — the only caller always passes `/cancel`.

### Documentation

- `CLAUDE.md` Quality Checks section now clears `vendor/pestphp/pest-plugin-mutate/.temp/mutations` before the mutation run and warns about the `pest-plugin-mutate` cache trap (escapes that look green locally because the kill-result cache survives across runs) and the `assertSent` early-return-true anti-pattern.

## [1.2.0] - 2026-05-07

### Added

- `PixAutomaticResource` (resolved via `AsaasClient::pixAutomatic()`) covering the `/pix/automatic/*` endpoints: `createAuthorization()`, `listAuthorizations()`, `findAuthorization()`, `cancelAuthorization()`, `listPaymentInstructions()`, `findPaymentInstruction()`, plus `allAuthorizations()` / `allPaymentInstructions()` lazy iterators.
- Pix Automático request DTOs (`AuthorizationRequest` with nested `ImmediateQrCode` coercion) and enums (`PixAutomaticAuthorizationStatus`, `PixAutomaticPaymentInstructionStatus`, `PixAutomaticFrequency`).
- Pix recurring outflow methods on `PixTransactionResource`: `listRecurrings()`, `findRecurring()`, `cancelRecurring()`, `listRecurringItems()`, `cancelRecurringItem()`, plus `allRecurrings()` / `allRecurringItems()` lazy iterators.
- `PixRecurringStatus` and `PixRecurringItemStatus` enums.

### Fixed

- `CreatePaymentRequest` now exposes the `authorizeOnly` flag for credit-card pre-authorization. Previously this field was silently dropped because `fromArray()` ignored it, breaking the typed pre-auth → `captureAuthorized()` flow.

## [1.1.0] - 2026-05-05

### Added

- `MyAccountResource` (resolved via `AsaasClient::myAccount()`) covering the `/myAccount/*` endpoints used during subaccount onboarding: `status()`, `commercialInfo()` / `updateCommercialInfo()`, `documents()`, `uploadDocumentFile()`, `deleteDocumentFile()`, `bankAccount()` / `updateBankAccount()`, and `delete()`.
- `DocumentType` enum (`Identification`, `SocialContract`, `EntrepreneurRequirement`, `Minutes`, `Custom`) for KYC document uploads.
- Request DTOs: `CommercialInfoRequest` (partial-update), `AccountBankAccountRequest`, `DeleteAccountRequest`.
- `Connector::postMultipart()` for `multipart/form-data` uploads, implemented in `AsaasConnector` with state restoration so the shared `PendingRequest` returns to JSON mode after the upload.
- README section documenting the white-label subaccount onboarding flow end-to-end, including the distinction between the two multi-tenant patterns (existing Asaas account vs. white-label subaccount).

### Changed

- `Connector` interface now requires `postMultipart()`. Custom `Connector` implementations must add this method (returning a failure result is acceptable for connectors that do not support uploads).

## [1.0.0] - 2026-05-04

Initial public release. See [README](README.md) for full feature documentation.

### Added

- Asaas API coverage: Payments, Pix, Pix Transactions, Transfers, Webhooks, Invoices, Accounts, Credit Cards, Bill Payments, Statements.
- Standalone (`AsaasClient::for()`) and Laravel (`Asaas` facade + auto-discovered `AsaasServiceProvider`) usage.
- Result-based error handling (`AsaasResult`, `AsaasPaginatedResult`) with opt-in `ThrowsOnFailure` trait.
- Typed Request DTOs with raw-array fallback on every mutation method.
- Pagination helpers `paginate()` and `all()` (generator).
- `WebhookVerifier` with timing-safe token comparison and configurable IP allowlist.

[2.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.4.0...v2.0.0
[1.4.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/releases/tag/v1.0.0
