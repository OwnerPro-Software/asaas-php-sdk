# Cross-cutting fields not in `asaas_openapi.json`

This file documents wire-valid fields and endpoints that the SDK supports but that are absent from the upstream Asaas OpenAPI export (`specs/asaas_openapi.json`) and therefore from the per-domain split files (`specs/domains/*.json`). Every entry cites the canonical `docs.asaas.com` page that documents it.

Audit methodology MUST consult this file alongside `specs/domains/*.json` before classifying any field/endpoint as wire-undocumented. See `feedback_doc_audit_methodology.md` for the lookup chain.

Maintenance rule: if a future spec refresh adds an entry here to `asaas_openapi.json`, delete the entry here in the same commit.

---

## `accessTokenConfig`

- **Endpoint:** `POST /v3/accounts` (subaccount create)
- **Spec gap:** absent from `specs/domains/accounts.json` (`AccountSaveRequestDTO`)
- **Source:** https://docs.asaas.com/docs/gerenciamento-de-permissões-de-chaves-de-api
- **Shape:**
  ```json
  "accessTokenConfig": {
     "name": "Chave de Integração Financeira",
     "permissions": [
        { "name": "PAYMENT", "scope": "READ_WRITE" },
        { "name": "TRANSFER", "scope": "READ" },
        { "name": "WEBHOOK", "scope": "READ_WRITE" }
     ]
  }
  ```
- **Default when omitted:** Asaas issues an API key with **all-permissions READ_WRITE**. Callers who need scoped tokens must send `accessTokenConfig` on create — there is no documented post-create way to scope the initial key.
- **Production motivation:** without this field, `POST /v3/transfers` from the subaccount returns "A chave de API fornecida não possui permissão" because the subaccount's API key lacks `TRANSFER` permission. See commit `0c49b9f`.
- **DTOs:** `OwnerPro\Asaas\Account\Request\AccountRequest::$accessTokenConfig`, `OwnerPro\Asaas\Account\Request\AccessTokenConfig`.

---

## `permissions[]` on access-token create

- **Endpoint:** `POST /v3/accounts/{id}/accessTokens`
- **Spec gap:** absent from `specs/domains/accounts.json` (`CustomerApiAccessTokenSaveRequestDTO` lists only `name` and `expirationDate`)
- **Source:** https://docs.asaas.com/docs/gerenciamento-de-permissões-de-chaves-de-api ("Você também pode atualizar as permissões de chaves já existentes" → directs to the subaccount access-token endpoints)
- **Shape:** same `permissions[]` array as `accessTokenConfig.permissions` (above).
- **Default when omitted:** all-permissions READ_WRITE (per concept-page).
- **DTOs:** `OwnerPro\Asaas\Account\Request\CreateAccessTokenRequest::$permissions`, `OwnerPro\Asaas\Account\Request\AccessTokenPermissionConfig`.

---

## `permissions[]` on access-token update

- **Endpoint:** `PUT /v3/accounts/{id}/accessTokens/{accessTokenId}`
- **Spec gap:** absent from `specs/domains/accounts.json` (`CustomerApiAccessTokenUpdateRequestDTO` lists only `name`, `enabled`, `expirationDate`)
- **Source:** https://docs.asaas.com/docs/gerenciamento-de-permissões-de-chaves-de-api
- **Shape:** same `permissions[]` array as create.
- **DTOs:** `OwnerPro\Asaas\Account\Request\UpdateAccessTokenRequest::$permissions`.

### Permission names (enum, applies to all `permissions[]` slots)

`CUSTOMER`, `PAYMENT`, `PAYMENT_REFUND`, `CHARGEBACK`, `INSTALLMENT`, `SUBSCRIPTION`, `PIX_CREDIT`, `PAYMENT_LINK`, `CHECKOUT`, `CREDIT_CARD`, `ANTICIPATION`, `ANTICIPATION_CONFIG`, `ESCROW`, `ESCROW_CONFIG`, `CREDIT_BUREAU`, `PAYMENT_DUNNING`, `FISCAL_INFO`, `INVOICE`, `PIX_DEBIT`, `PIX_ADDRESS_KEY`, `PIX_RECURRING`, `PIX_TRANSACTION`, `PIX_AUTOMATIC`, `TRANSFER`, `BILL`, `MOBILE_PHONE_RECHARGE`, `FINANCIAL_TRANSACTION`, `ACCOUNT_INFO`, `PAYMENT_CHECKOUT_CONFIG`, `WEBHOOK`, `SUB_ACCOUNT`, `ACCOUNT_DOCUMENT`, `CUSTOMER_NOTIFICATION`.

### Scope values (enum)

`READ`, `READ_WRITE`.

---

## `GET /v3/payments/{id}/refunds`

- **Spec gap:** absent from `specs/domains/payments.json`. Spec only documents `POST /v3/payments/{id}/refund` (singular, to create a refund) — not the list-refunds GET.
- **Source:** https://docs.asaas.com/reference/listar-estornos-de-uma-cobranca
- **SDK method:** `OwnerPro\Asaas\Payment\PaymentResource::listRefunds(string $id): AsaasResult`.

---

## `POST /v3/payments/{id}/bankSlip/refund`

- **Spec gap:** absent from `specs/domains/payments.json`.
- **Source:** https://docs.asaas.com/reference/estornar-boleto and https://docs.asaas.com/changelog/endpoint-para-estorno-de-boleto.
- **SDK method:** `OwnerPro\Asaas\Payment\PaymentResource::refundBankSlip(string $id, array|RefundPaymentRequest $data = []): AsaasResult`.
- **Body:** same shape as the standard `RefundPaymentRequest` (`value?`, `description?`, `refundOnCustomerCreditCard?`). Documented identical to the credit-card refund.

---

## `GET /v3/payments/{id}/chargeback`

- **Spec gap:** absent from `specs/domains/payments.json`.
- **Source:** https://docs.asaas.com/reference/recuperar-um-unico-chargeback
- **SDK method:** `OwnerPro\Asaas\Payment\PaymentResource::getChargeback(string $id): AsaasResult`.

---

## `remoteIp` on `POST /v3/payments/{id}/payWithCreditCard`

- **Endpoint** is in spec (`specs/domains/payments.json:4489`), but the request body schema only declares `creditCard`, `creditCardHolderInfo`, `creditCardToken`. `remoteIp` is absent from the schema.
- **Source:** https://docs.asaas.com/docs/cobrancas-via-cartao-de-credito — JSON example shows `"remoteIp": "116.213.42.532"` inside the body.
- **Field type:** `string` (IPv4 or IPv6).
- **Why it matters:** Asaas anti-fraud uses `remoteIp` to score the transaction. Omitting it works server-side but increases the chance of soft-decline.
- **DTO:** `OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest::$remoteIp`.

---

## `/v3/wallets/` GET query parameters

- **Endpoint** is in spec (`specs/domains/my-account.json:2307`), but the `parameters` array is empty.
- **Source:** https://docs.asaas.com/reference/recuperar-walletid — page omits the section, but the endpoint follows Asaas's universal `offset`/`limit` pagination convention used on every other list endpoint.
- **Accepted query params:** `offset` (int, default 0), `limit` (int, default 100).
- **SDK method:** `OwnerPro\Asaas\Account\MyAccountResource::wallets(array $query = []): AsaasPaginatedResult`.
