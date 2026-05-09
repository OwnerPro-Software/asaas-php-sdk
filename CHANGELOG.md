# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/releases/tag/v1.0.0
