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

---

## `/v3/payments/{id}/refunds` GET query parameters

- **Endpoint** is in spec (`specs/domains/payment-refunds.json`), but its `parameters` array carries only the `id` path param.
- **Source:** https://docs.asaas.com/reference/listar-estornos-de-uma-cobranca — page documents no query parameters. The *response* schema in the same spec file declares the standard envelope (`object`, `hasMore`, `totalCount`, `limit`, `offset`, `data`), so the endpoint is paginated even though the request side is undocumented; it follows Asaas's universal `offset`/`limit` convention as `/v3/wallets/` does.
- **Accepted query params:** `offset` (int, default 0), `limit` (int, default 100). **Unverified against a live endpoint** — unlike the other entries here, this one is inferred from the response envelope rather than read off a doc page. `PaginatesResults::all()` terminates on a repeated page and on `totalCount` as well as on an empty page, so a server that ignored `offset` stops the walk — with a `PAGINATION_STALLED` error — rather than looping.
- **SDK methods:** `OwnerPro\Asaas\Payment\PaymentResource::listRefunds(string $id, array $query = []): AsaasPaginatedResult`, `::allRefunds(string $id, array $filters = []): Generator`.

---

## `/v3/payments/{id}/documents` GET query parameters

- **Endpoint** is in spec (`specs/domains/payment-documents.json`), but its `parameters` array carries only the `id` path param — the same gap as `/v3/payments/{id}/refunds` above.
- **Source:** https://docs.asaas.com/reference/listar-documentos-de-uma-cobranca — page documents no query parameters. The *response* schema in the same spec file declares the standard envelope, so the endpoint is paginated even though the request side is undocumented.
- **Accepted query params:** `offset` (int, default 0), `limit` (int, default 100). **Unverified against a live endpoint**, inferred from the response envelope; the same `all()` brakes described above apply.
- **SDK method:** `OwnerPro\Asaas\Payment\PaymentResource::listDocuments(string $paymentId, array $query = []): AsaasPaginatedResult`.

---

## `GET /v3/pix/addressKeys/external` (DICT lookup of a third-party key)

- **Endpoint** is absent from `specs/domains/pix.json` and `specs/asaas_openapi.json` — the export only carries the own-account key endpoints (`/v3/pix/addressKeys`, `/v3/pix/addressKeys/{id}`, `/v3/pix/tokenBucket/addressKey`).
- **Source:** https://docs.asaas.com/reference/consultar-chave-pix
- **Query params:** `type` (enum `CPF`, `CNPJ`, `EMAIL`, `PHONE`, `EVP`), `key` (string, the key value). The doc page marks both as optional, but the lookup is meaningless without them, so the SDK method requires both.
- **Response (200):** `type`, `key`, `ispb`, `ispbName`, `financialInstitution{id,name,code}`, `owner{name,cpfCnpj}` (masked). Errors follow the standard envelope (`400` on an unknown key, `401` on an invalid API key, `403` when the GET carries a body).
- **Why it matters:** `findKey()` reads a key registered on the calling account by its Asaas id; this is the only way to resolve any Pix key in the ecosystem to its owner and institution before a transfer.
- **SDK method:** `OwnerPro\Asaas\Pix\PixResource::findExternalKey(string $key, PixAddressKeyType|string $type): AsaasResult`.
