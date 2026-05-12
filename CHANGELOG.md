# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-05-12

Major release. Spec-alignment work driven by a full audit of the SDK against `specs/asaas_openapi.json`, followed by the deferred backlog closing the remaining endpoint gaps (fiscal info, payment documents, escrow, payment checkout personalisation, wallets, lean payments, and split lookup). Closes 19 documented field gaps, the wrong-verb bug on `TransferResource::cancel()`, the missing `GET /v3/accounts/{id}/accessTokens/{accessTokenId}` endpoint, the new `accessTokenConfig` / `permissions` payload pieces (without which subaccounts created via the SDK inherited a key with no `TRANSFER` permission and blocked the production flow), and adds 27 new endpoints across 9 domains.

### Breaking

- `TransferResource::cancel($id)` now sends **DELETE** (was POST). Asaas's spec requires DELETE on `/v3/transfers/{id}/cancel` — POST silently failed or hit the wrong handler in some configurations. Any consumer wrapping the SDK's HTTP layer (e.g. retry middleware keyed by method) must update accordingly.
- `PayWithCreditCardRequest::$creditCard`, `$creditCardHolderInfo`, and `$remoteIp` are now optional (`?CreditCard`, `?CreditCardHolderInfo`, `?string`) to support the new token-only flow. Constructors using positional args keep working; consumers relying on the previous "throws when missing" semantics will no longer see those exceptions and must validate at their own boundaries.
- `Connector::postMultipart()` no longer throws on an empty `$files` array — `$files` is now optional with a `[]` default. Custom `Connector` implementations must update their signature to match (`array $files = []`). The change unblocks form-only multipart endpoints (`/v3/fiscalInfo/`, `/v3/myAccount/paymentCheckoutConfig/`) where the binary file is optional. The previous "at least one file" invariant was an artificial guard, not a protocol requirement.

### Added — DTO fields

- `CreatePaymentRequest`: `daysAfterDueDateToRegistrationCancellation`, `installmentCount`, `installmentValue`, `totalValue`, `pixAutomaticAuthorizationId`.
- `UpdatePaymentRequest`: `daysAfterDueDateToRegistrationCancellation`, `callback`.
- `PayWithCreditCardRequest`: `creditCardToken` (lets you pay using a saved-card token without sending card details again).
- `AccountRequest`: `loginEmail`, `webhooks` (list of `CreateWebhookRequest`; coerced from raw arrays), and `accessTokenConfig` (`{name, permissions[]}`) — set the initial subaccount API key's name and permission scope at creation time so the key ships ready for `TRANSFER`, `PIX_*`, `WEBHOOK`, etc. without a manual visit to the painel. If omitted, Asaas's default (all permissions in `READ_WRITE`) still applies.
- `AccessTokenRequest`: `permissions` (`list<AccessTokenPermissionConfig>`) — same shape, accepted by `accounts()->updateAccessToken(...)` for adjusting an existing key's permissions.
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
- `AccountResource::createAccessToken($accountId, $data = null)` now accepts an optional `AccessTokenRequest` (or array) body with `name`/`expirationDate`.
- `AccountResource::findAccessToken(string $accountId, string $tokenId)` — `GET /v3/accounts/{id}/accessTokens/{accessTokenId}`, the single-token retrieve that the original audit missed.
- `AccountResource::escrowConfig(string $accountId)`, `setEscrowConfig(string $accountId, array|EscrowConfigRequest $data)`, `defaultEscrowConfig()`, `setDefaultEscrowConfig(array|EscrowConfigRequest $data)` — manage escrow accounts at both the per-subaccount level (`/v3/accounts/{id}/escrow`) and the default-for-all level (`/v3/accounts/escrow`).
- `MyAccountResource::accountNumber()` — `GET /v3/myAccount/accountNumber`.
- `MyAccountResource::fees()` — `GET /v3/myAccount/fees/`.
- `MyAccountResource::paymentCheckoutConfig()` and `updatePaymentCheckoutConfig(array|PaymentCheckoutConfigRequest $data, mixed $logoFile = null, ?string $logoFilename = null)` — GET / POST `/v3/myAccount/paymentCheckoutConfig/`. The save call is multipart and accepts an optional logo binary.
- `MyAccountResource::wallets(array $query = [])` — `GET /v3/wallets/`, the walletId listing.
- `StatementResource::balance()` — `GET /v3/finance/balance`.
- `StatementResource::paymentStatistics(array $query = [])` — `GET /v3/finance/payment/statistics`.
- `StatementResource::splitStatistics(array $query = [])` — `GET /v3/finance/split/statistics`.
- `TransferResource::createInternal(array|InternalTransferRequest $data)` — `POST /v3/transfers/` (trailing slash) for the internal-Asaas-account flow with `walletId`.

### Added — new resources

- `OwnerPro\Asaas\FiscalInfo\FiscalInfoResource` (resolved via `Asaas::fiscalInfo()` / `$client->fiscalInfo()`) covering `/v3/fiscalInfo/*`: `recover()`, `save(array|FiscalInfoRequest $data, mixed $certificateFile = null, ?string $certificateFilename = null)` (multipart, optional A1 certificate), `municipalOptions()`, `services()`, `federalServiceCodes()`, `nbsCodes()`, `operationIndicatorCodes()`, `taxClassificationCodes()`, `taxSituationCodes()`, and `configureNationalPortal(bool $enabled)`.
- `OwnerPro\Asaas\Payment\LeanPaymentResource` (resolved via `Asaas::leanPayments()` / `$client->leanPayments()`) covering `/v3/lean/payments/*` — slim-response variants of the standard payment endpoints: `create()`, `createWithCreditCard()`, `find()`, `captureAuthorized()`, `restore()`, `refund()`, `receiveInCash()`, `undoReceivedInCash()`. Reuses the same request DTOs as `PaymentResource`.

### Added — DTOs and enums

- `OwnerPro\Asaas\FiscalInfo\Request\FiscalInfoRequest` — partial-update DTO for the `POST /v3/fiscalInfo/` body (every form field except the binary).
- `OwnerPro\Asaas\Payment\PaymentDocumentType` enum (`INVOICE`, `CONTRACT`, `MEDIA`, `DOCUMENT`, `SPREADSHEET`, `PROGRAM`, `OTHER`).
- `OwnerPro\Asaas\Payment\Request\UpdatePaymentDocumentRequest` — required-fields DTO for `PUT /v3/payments/{id}/documents/{documentId}`.
- `OwnerPro\Asaas\Account\Request\EscrowConfigRequest` — `{daysToExpire, enabled?, isFeePayer?}` for the four escrow-config endpoints.
- `OwnerPro\Asaas\Account\Request\PaymentCheckoutConfigRequest` — `{logoBackgroundColor, infoBackgroundColor, fontColor, enabled?}` for the checkout-personalisation save.

### Migration notes

- `discount` / `interest` / `fine` on `CreatePaymentRequest` and `UpdatePaymentRequest`: passing a float keeps working — the SDK wraps it as `Discount(value: $float)` / `Interest(...)` / `Fine(...)`. Wire output is now the documented object shape (`{value: ..., dueDateLimitDays: ..., type: ...}`), which is what Asaas's validator describes. If you were relying on the older scalar-on-wire behavior, audit your downstream consumers accordingly.
- `PayWithCreditCardRequest::fromArray` no longer throws on missing `creditCard`, `creditCardHolderInfo`, or `remoteIp` — required validation moves to the server. Provide either `creditCardToken` **or** the full card/holder/IP triple.
- `TransferResource::cancel()`: confirmed via the audit that the spec uses DELETE. If you reverse-proxy or log by HTTP method, update mappings.
- Custom `Connector` implementations: the `postMultipart()` interface signature changed from `(string, array, array)` to `(string, array, array = [])`. Native PHP signatures must include the default value to remain LSP-compatible.

### Internal

- `Discount`, `Interest`, `Fine`, `Callback` each expose a static `coerce()` helper, normalising union inputs (`array | float | DTO | Missing | null`) into a normalized DTO instance. This kept `UpdatePaymentRequest`'s constructor cognitive complexity under PHPStan's class threshold without weakening the public API.
- Wire-level integration tests added to `AccountResourceTest` pinning that `POST /v3/accounts` carries `accessTokenConfig` end-to-end (both via raw array and via `AccessTokenConfig` DTO with enum cases), and that `PUT /v3/accounts/{id}/accessTokens/{tokenId}` carries `permissions` in the documented `{name, scope}` shape. Closes the e2e coverage gap on the feature that motivated this release (subaccount keys ship with `TRANSFER` permission so `POST /transfers` no longer blocks production).

### Documentation

- README: documented the `Discount` / `Interest` / `Fine` value objects in the nested-DTOs table and added a usage example showing both legacy float and typed-DTO forms — this is the load-bearing breaking change of 2.0.0 (property type changed from `?float` to `?Discount|?Interest|?Fine`) and was previously CHANGELOG-only.
- README: updated the Custom Connector example to match the new `postMultipart(string, array, array = [])` signature.

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

[Unreleased]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.4.0...v2.0.0
[1.4.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/releases/tag/v1.0.0
