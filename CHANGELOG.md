# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Bug-hunt passes over the whole package: thirty defects found by auditing the
resources against `specs/domains/*.json`, the DTO factories against their own
constructors, and the Laravel/testing seams against the framework internals
they rely on. Every fix is pinned by a test that fails without it.

**This release is a major.** Several of the corrections below change a signature
or start throwing where the previous version silently accepted the input; they
are collected under *Breaking* so an upgrade can be planned from one list.

### Breaking

- **`serialize()` now throws on `AsaasClient` and `AsaasConnector`.** Both reach
  the API key, and `serialize()` honours neither `__debugInfo()` nor the
  VarDumper caster, so a queued job holding a client wrote the key into the
  jobs table in clear. Both now raise `LogicException`. A job that carried the
  client as a property must resolve it from the container inside `handle()`
  instead, or rebuild it with `AsaasClient::for()` from your own secret store.
  See *Security* below.
- **`payments()->listRefunds()` returns `AsaasPaginatedResult`** — see *Changed*.
- **`UpdateInvoiceRequest::$municipalServiceName` was removed.** The update
  endpoint does not accept the field. Passing it as a named argument now raises
  `TypeError`; drop it from the call.
- **`payments()->finishEscrow()` renamed its parameter to `$escrowId`.** It
  always took the escrow guarantee id rather than the payment id — the name said
  otherwise. Only named-argument call sites are affected.
- **`pix()->createKey()` rejects every key type but `EVP`.** The create endpoint
  mints random keys only; the other four types are registered in the Asaas panel.
  What used to be a remote 400 is now an `InvalidArgumentException` at
  construction.
- **`FakeAsaasClient::recorded()` yields `?Response`.** A request that failed in
  transport is recorded with no response. Matcher closures typed
  `fn (Request $r, Response $response)` raise `TypeError` the moment such a
  request is in the stream — widen them to `?Response`.
- **`assertSent()` rejects two closures**, and **stub/assertion patterns reject
  an absolute URL, a leading `v3/` segment in any casing, or whitespace inside
  the pattern**. Each form previously produced an assertion that could not fail;
  all now throw `InvalidArgumentException`. Surrounding whitespace is still
  trimmed — only interior whitespace is refused.
- **`stubPages()` rejects a `totalCount` the walk exhausts early.** A non-final
  page declaring a count the sequence has already delivered by the end of that
  page made `all()` stop there and emit `PAGINATION_INCONSISTENT` into the row
  stream, dropping every page behind it — the fake manufacturing a contradiction
  rather than a test describing one. It now throws at registration. Declare the
  count of the whole walk, or leave the key out; `totalCount: 0` is unaffected,
  being what an envelope omitting the key reports.
- **`MasksSensitiveData::mask()` emits a constant-width fill.** Tests asserting
  on the exact masked string need updating — see *Security*.
- **An empty `permissions` list is rejected.** `permissions: []` on
  `CreateAccessTokenRequest`, `UpdateAccessTokenRequest` or the inline
  `accessTokenConfig` now raises `InvalidArgumentException` instead of being
  omitted from the body. See *Security*.
- **`stubPages()` overrides `hasMore` on the last page.** A final page declaring
  `hasMore: true` promised a page the sequence cannot serve and ran the walk off
  the end into Laravel's raw "response sequence is empty"; it is now forced to
  `false`. On non-final pages `hasMore` remains a default, so a page declaring
  `hasMore: false` still ends the walk there — that is the only way to pin the
  termination contract. Every other key a page declares is honoured either way.
- **`Connector::postMultipart()` requires a `filename` on every entry of
  `$files`.** Omitting it never reached the filename guard at all: `attach()`
  leaves the key out and the HTTP client substitutes the local file's name,
  disclosing it. The key was already passed by every caller in the SDK; a part
  that is not a file belongs in `$data`. Omitting it now raises
  `InvalidArgumentException`. See *Security*.
- **A multipart part may not carry its own `Content-Disposition` header.**
  Guzzle writes its own only when the caller supplied none, so this header
  silently replaced the `name` and `filename` the guard had just validated.
  Rejected with `InvalidArgumentException`; pass the part's `name` and
  `filename` instead. `Content-Length` is deliberately still accepted even
  though Guzzle defers to it the same way — a non-seekable stream cannot report
  its size, and supplying the length is the only way to describe such a part.
- **`$data` may not describe file parts.** Laravel promotes any `$data` entry
  that is an array carrying both `name` and `contents` to a multipart element,
  with its own `filename` and `headers` — none of which passed
  `ContentDispositionGuard`. Writing the part in the wrong argument therefore
  bypassed the whole guard, and a bare file handle in `$data` reached the same
  local-filename fallback. Both now raise `InvalidArgumentException`.
- **Invalid stub and assertion patterns now throw where they are written.** The
  fake validated a pattern inside its router, which is reached only when no
  earlier stub matched first — so a bad pattern sitting behind a catch-all was
  never validated at all, and the caller silently got the generic stub. The
  check now runs in the constructor and in every method that registers a stub:
  `stub()`, `stubError()`, `stubPages()`, `stubException()`,
  `stubRequestNotDelivered()` and `stubIndeterminateResult()`. Tests that
  asserted the throw at request time need to expect it at registration.
- **With `throwOnTransportFailure: true`, a 5xx now throws
  `IndeterminateResultException` instead of returning a failure result.** The
  connector treated `$response->failed()` — 4xx *and* 5xx — as a definitive
  answer. A 5xx is not Asaas answering about the operation: it is the server, or
  a proxy in front of it, reporting that it could not answer, so the request may
  well have been processed. On `POST /v3/transfers` that misclassification is
  the difference between reconciling and retrying real money. The exception
  carries `phase: 'server'` (a new value in the `phase` union) and the received
  `response`, so status and body stay loggable. **4xx is unchanged** — it stays
  an `AsaasResult` failure, including `408` and `429`. With the flag off
  (default) nothing changes: 5xx still returns a failure result.
  `FakeAsaasClient::stubIndeterminateResult()` accepts `phase: 'server'`, which
  stubs a `502`.
- **`$result->errors[0]['description']` can now hold `***` where it held the
  response body.** This is the one redaction in the SDK that replaces a value
  rather than a view of it, because a description is free text and nothing
  downstream can recognise a credential inside it at print time — see
  *Security*. Code matching on the description of a non-canonical error no
  longer sees the raw body; read `$result->response->body()` for that.

### Security

- **`dump()`, `dd()` and framework error pages published raw secrets.** The SDK
  relied on `__debugInfo()` alone, which PHP's `var_dump()` honours but Symfony
  VarDumper — behind Laravel's `dump()`/`dd()` and the Ignition/Flare error
  pages — *merges* over the real property list rather than replacing it, so the
  API key, PAN, CVV and CPF/CNPJ stayed visible next to their masked twins. A
  VarDumper caster now replaces the list outright. It is keyed on the new
  `Support\Redactable` interface, so every implementor is covered by one
  registration, installed from `bootstrap/redaction.php` — a Composer `files`
  autoload entry, included from `vendor/autoload.php` before any framework
  boots. Custom classes join by implementing the interface, with no wiring of
  their own.

  Registering from a service provider was tried first and does not work:
  `AbstractCloner` copies `$defaultCasters` in its constructor, and Laravel
  builds the cloner behind `dump()`/`dd()`/Ignition during
  `FoundationServiceProvider::register()` — which finishes before any `boot()`
  begins. The caster never reached that cloner, so a dumped `AsaasResult`
  printed the live `apiKey` in full and merely appended the redacted view below
  it. The `Support\DumpRedaction` class that expressed that registration is gone
  with it; nothing replaces it, because there is no longer a call to make.
- **`$result->response` vanished from an encoded result.** `RawResponse` holds
  its `Illuminate` response privately and implemented no `JsonSerializable`, so
  `json_encode($result)` — the path `Log::info(['result' => $result])` takes
  through Monolog — wrote `"response":{}` where the redacted status, headers and
  body belonged. It now serializes the same view the dumper shows.
- **An upstream `message` reached `$result->errors` unscrubbed.** `ErrorEnvelope`
  scrubbed the response body it pastes into a synthesized description but not
  the `message` field beside it, which is the branch that wins when present. A
  proxy echoing a subaccount payload there put a live key into the errors row and
  the exception message. Both branches now run the same scrub.
- **Upload names could disguise what they read as.** The filename and part-name
  guard rejected quotes, backslashes and control characters, but not the Unicode
  bidirectional formatting characters: `fpdf<U+202E>gpj.exe` renders as an image
  name in the Asaas panel while remaining an executable. They are now rejected,
  matched as UTF-8 bytes so a Latin-1 filename is not failed on an
  unreadable-subject error.
- **A body that could not be re-encoded was printed raw.** `SecretRedactor::scrubJson()`
  answered `null` when `json_encode()` refused the payload it had just scrubbed,
  and both callers fall back to the untouched body on `null` — so a body
  `json_decode()` accepts but `json_encode()` cannot round-trip (a float literal
  past the double range decodes to `INF`) printed the live key the scrub had
  already replaced. A decoded body is never answered raw now; the failure
  answers a placeholder instead.
- **The multipart field-name guard was reachable around.** `ContentDispositionGuard`
  validated `$files[].name`, but a `$data` key lands in the same unescaped
  `Content-Disposition: form-data; name="%s"` slot, so a caller forwarding a
  browser-supplied field name could close the quote, append part headers of its
  own and forge a `documentFile` part with a filename no guard ever saw. `$data`
  keys now go through the same guard. Field names are otherwise unaffected;
  an empty one is now rejected, since it names no form field.
- **An upload filename of `'0'` leaked the local file's name.** The guard
  rejected an empty name because the HTTP client then falls back to the stream's
  `uri` metadata — but it tested `=== ''` while Guzzle tests `empty()`, so the
  one-character name `'0'` passed the guard and was substituted on the wire.
  (Guzzle special-cases `'0'` where it writes the header, but not where it
  decides to substitute.) Both are now rejected, as is an omitted `filename` —
  see *Breaking*. `'0.png'` and the like are unaffected.
- **`username` is redacted out of responses.** The `/fiscalInfo/` endpoints echo
  the municipal-portal login, and `FiscalInfoRequest` already marks it
  `#[SensitiveParameter]` and masks it on the way out — redacting the request
  while printing the response in full was not a defensible line. It is the only
  field named `username` in `specs/domains/`.
- **Response headers go through the scrub too.** Asaas puts no credential in
  one, but `RawResponse` is also shown for whatever answered in its place, and a
  proxy echoing an `authToken` header is worth not printing. `headers()` still
  answers the untouched values, as `body()` does.
- **A rejected request could print a live credential in `$result->errors`.**
  When the response is not the canonical error envelope, `ErrorEnvelope`
  synthesizes an `UNKNOWN_ERROR` row whose `description` is the response body —
  and a rejected `POST /accounts` answers with the subaccount payload, `apiKey`
  included. `$errors` is the one part of a result no downstream scrub reached:
  the result classes scrub `data` by field name, and a credential pasted into a
  description is a string, not a field they can recognise. The same `dump()`
  therefore showed `apiKey: ***` for the response body and the live key two
  fields away. The body is now scrubbed before it becomes a description — and
  before it is truncated, for the reason given above. A body that is not JSON
  has no field names to key on and is unchanged. Unlike every other redaction
  here, this one replaces the stored value rather than the view of it: the scrub
  has to happen when the row is synthesized, because a description is free text
  and no print-time hook can pick a credential out of it. Listed under
  *Breaking* as well.
  A row Asaas passed through untouched needs the other kind of cover, so the
  result classes now scrub `errors` by field name in `__debugInfo()` /
  `jsonSerialize()` as they already did for `data`. The two layers answer
  different questions — a credential *pasted into* a description versus one
  carried *as a field* on a row — and neither substitutes for the other.
- **Credentials Asaas sends back were carried verbatim on public properties.**
  Redaction was request-side only, so `AsaasResult`, `AsaasPaginatedResult` and
  `RawResponse` disclosed any secret in the response body. Four response fields
  hold a live credential: `apiKey` (the subaccount key `POST /accounts` returns
  once), `accessToken`, `authToken` (echoed by `GET /webhooks`, one per row on a
  page) and `creditCardToken`. The new `Support\SecretRedactor` scrubs them
  recursively and case-insensitively, and the response body is scrubbed *before*
  it is truncated — truncating first leaves unparseable JSON, which falls
  through to the raw text the scrub exists to withhold.
  Both result classes also implement `JsonSerializable`, because the dump
  surface is not the one that reaches a log file: `Log::info('created',
  ['result' => $result])` hands the result to Monolog, which `json_encode()`s
  its context — and an encoder walking the public properties writes the live
  subaccount key, or every `authToken` on a page of `GET /webhooks`, into the
  log.
  Two gaps stay open by design and are documented in `README.md`: results still
  allow `serialize()` (unlike the client and the request DTOs, they are
  legitimately cached and queued), and `$result->data` is a plain array that
  nothing intercepts once a caller passes it on.
- **An empty `permissions` list silently minted an all-permissions key.**
  `permissions: []` was folded into an omission — and omitting the field is the
  documented signal for a key with `READ_WRITE` on everything
  (`specs/concept-fields.md`). The most restrictive-looking input therefore
  produced the most privileged key Asaas issues, and on `accounts()->create()`
  it defeated `accessTokenConfig` entirely, for which there is no documented
  post-create way to scope the initial key. `{"permissions": []}` has no
  documented meaning either, so there is no third option that both reaches the
  wire and behaves: `AccessTokenPermissionConfig::coerceList()` now throws
  `InvalidArgumentException` and the caller says which one they meant. The
  previous rationale — that `[]` is what `$request->validated()` yields for an
  absent list — was wrong: `validated()` omits absent keys entirely, so `[]`
  only ever arrives when a client explicitly sent one.
- **`serialize()` is refused on `AsaasClient` and `AsaasConnector`.** See
  *Breaking* above for the migration.
- **Upload filenames could break out of the `Content-Disposition` header.**
  Guzzle interpolates both `name` and `filename` into
  `Content-Disposition: form-data; name="..."; filename="..."` with no escaping,
  so a caller forwarding a browser-supplied `getClientOriginalName()` let the
  uploader forge extra form fields inside the request body. The new
  `Support\ContentDispositionGuard` strips directory components from the
  filename — an absolute local path would otherwise ship to Asaas, and an empty
  name makes Guzzle fall back to the stream's `uri` metadata and ship it anyway —
  and rejects a double quote, a **backslash** (RFC 2616 reads `\"` as a
  quoted-pair, so a trailing one escapes the closing quote just as effectively),
  every control character, and anything over 255 chars. The same guard now
  covers the per-part `headers` a caller reaching `Connector::postMultipart()`
  directly can supply: Guzzle writes each pair as `"{$name}: {$value}\r\n"` with
  no validation of its own (`MultipartStream::getHeaders()`), so a CR or LF in
  either half closes the part's header block and appends arbitrary headers — or
  a whole extra part. Header names must be RFC 7230 tokens; values must carry no
  control characters, but keep quotes and backslashes, which are ordinary
  outside a quoted string. Every caller-supplied value is validated before the
  first `attach()`: a rejection mid-loop would leave the already-attached files
  pending on the reused `PendingRequest` and smuggle them into the next upload.
- **The mask published the exact length of the value it hid.** One asterisk per
  hidden character distinguishes an 11-digit CPF from a 14-digit CNPJ and narrows
  a brute-force search over a token. `mask()` now emits a constant-width fill,
  and a value no longer than the visible suffix is masked whole. An empty value
  stays empty: a fill there would disguise an unset field as a redacted one and
  hide empty-payload bugs from the dump.

### Fixed

- **An equivalent stub pattern was accepted and never served.** `FakeAsaasClient`
  keyed its stub map by the raw pattern string while matching on the resolved
  glob, so `'payments/*'`, `'/payments/*'` and `' payments/*'` were three entries
  collapsing onto one: the first registered always won, and the rest sat there
  dead — no `NoMatchingStubException` is raised while some entry matches, so a
  test that thought it had re-stubbed an endpoint saw no error anywhere. The map
  is keyed by the resolved pattern now, so registering an equivalent one replaces
  the stub it is equivalent to, in place.
- **A walk could run without a ceiling.** Both of `all()`'s diagnostic brakes
  need a signal the envelope may withhold — `totalCount` reports 0 when omitted,
  and the stall check needs a repeated page. An endpoint ignoring `offset` while
  answering in an unstable order gives neither and just keeps saying `hasMore`. A
  10 000-page ceiling now ends such a walk with the new `PAGINATION_RUNAWAY`
  code; at the maximum page size that is a million rows, so it only fires on a
  fault.
- **`permissions: []` shipped on two of the three DTOs that carry it.**
  `AccessTokenConfig` deliberately collapsed an empty permissions list so a new
  subaccount key keeps its all-permissions `READ_WRITE` default, but
  `CreateAccessTokenRequest` and `UpdateAccessTokenRequest` carried the same
  field through plain `HasArrayFactory::toArray()`, which filters only `null`.
  Feeding either from `$request->validated()` — where an absent list arrives as
  `[]` — sent `{"permissions": []}`, a shape Asaas does not document and that
  leaves the key in an undefined permission state. The coercion now lives once
  on `AccessTokenPermissionConfig::coerceList()`, shared by all three.
- **Three `myAccount` endpoints dropped the trailing slash the spec declares.**
  `status()`, `commercialInfo()`/`updateCommercialInfo()` and `delete()` emitted
  `/myAccount/status`, `/myAccount/commercialInfo` and `/myAccount?removeReason=`
  while `specs/domains/my-account.json` — and this README — declare all three
  with the slash, and five sibling endpoints already preserved it.
  `POST /myAccount/commercialInfo/` carried the real exposure: should Asaas
  tighten its router to a 301, Guzzle's non-strict redirect handling downgrades
  POST to GET and the update would silently no-op.
- **A list that lost its sequence shipped as a JSON object.** `array_filter()`
  over a list leaves the surviving keys in place, and `json_encode()` renders
  the gap as an object — so `'split' => array_filter($splits, ...)` reached the
  wire as `{"split":{"1":{...}}}` where Asaas declares `"type": "array"`. The
  400 that came back named no field the caller could act on. `toArray()` now
  re-indexes integer-keyed arrays at the serialization boundary, covering
  `split`, `splitRefunds`, `permissions`, `webhooks`, `events` and
  `billingTypes` alike; string-keyed maps are left untouched.
- **An absolute pattern made `assertNotSent()` incapable of failing.** The fake
  prefixed its base URL onto every pattern without checking whether the pattern
  was already absolute, so `assertNotSent('https://api-sandbox.asaas.com/v3/payments*')`
  — the form muscle memory produces from `Http::assertNotSent()` — resolved to a
  doubled URL that matches nothing and passed unconditionally, as did
  `recorded()` and `assertSent(..., times: 0)`. Absolute patterns now throw
  `InvalidArgumentException` in stubs and assertions alike.
- **A lone `stub()` declaring `hasMore: true` hung `->all()` forever.** A single
  stub is one response replayed for every matching request, so it describes page
  one and nothing else — the walk kept re-requesting the same rows and never
  terminated, hanging the consumer's test suite with no error to read. Such a
  stub now serves the declared page at offset 0 and the empty terminal page a
  real endpoint would answer with beyond it, so `->list()` still observes the
  declared `hasMore` while `->all()` ends. `stubPages()` remains the way to model
  a real multi-page walk, and it now rejects an empty page list at registration
  instead of failing later as an exhausted sequence.
- **An empty upstream `description` produced an exception with no message.**
  Canonical error envelopes reach the caller verbatim, so a row carrying
  `"description": ""` left `AsaasRequestException::getMessage()` empty — the
  "nothing to act on in the log" outcome `ErrorEnvelope` documents as the thing
  it prevents. The default message now also covers the empty-string case; the
  `ErrorEnvelope` docblock has been corrected to scope its non-empty guarantee to
  the rows it synthesizes.
- **IPv4-mapped IPv6 addresses failed the webhook allowlist.**
  `WebhookVerifier::isFromAsaas()` compared raw strings, but a dual-stack
  listener or proxy reports an IPv4 client as `::ffff:52.67.12.206` — the same
  host as `52.67.12.206`, never string-equal to it. Every genuine webhook was
  rejected and reconciliation stopped silently. Addresses are now compared as
  packed bytes with the IPv4-mapped range folded to its IPv4 form, so either
  notation matches on either side; non-IP values still never match.
- **`accessTokenConfig` shipped an undefined permission state.**
  `{"permissions": []}` survived the empty-config collapse, yet omitting
  `accessTokenConfig` entirely is what mints the documented all-permissions
  `READ_WRITE` key — an explicitly empty list has no documented meaning. It was
  first folded into an omission; that turned out to silently mint the very key
  the caller was trying to restrict, so the list is now **rejected** instead —
  see *Breaking* and *Security*. Nothing about `permissions: []` is silently
  accepted any more.
- **A nested all-optional payload shipped as a JSON array.** `accessTokenConfig`
  carries two optional fields, so `accounts()->create([… 'accessTokenConfig' => []])`
  — the shape Laravel's `$request->validated()` produces for an empty
  client-supplied object — serialized to `"accessTokenConfig": []`, an array
  where Asaas declares an object. `JsonBody` only rescues the top-level body;
  this was the same defect one level deeper. A config with nothing to configure
  is now omitted from the request entirely, via `AccessTokenConfig::coerce()`.
- **`FakeAsaasClient::stub()` rejected `Http::sequence()`.** The parameter union
  omitted `ResponseSequence`, even though the constructor, `AsaasClient::fake()`
  and the private `register()` all accept and handle one. Registering a sequence
  through the fluent path raised a `TypeError` while the documented constructor
  form worked, leaving multi-call stubs unreachable after construction.
- **Explicit `null` reached non-nullable constructor parameters.**
  `UpdatePaymentRequest`, `UpdateInvoiceRequest` and `UpdateWebhookRequest` built
  their DTOs with `array_key_exists()`, so `fromArray(['dueDate' => null])`
  forwarded `null` into a `string|Missing` parameter and raised an uncaught
  `TypeError` — while the same input on `FiscalInfoRequest` /
  `CommercialInfoRequest` was silently omitted. Feeding an update DTO from
  Laravel's `$request->validated()`, which legitimately yields `null` for an
  untouched optional field, escaped the Result-based error contract on three
  resources and worked fine on the others. No request-body field is nullable, so
  all five now agree: an explicit `null` means "omit".
- **An all-optional payload shipped as a JSON array.** `json_encode([])` yields
  `[]`, so `payments()->update($id, [])`, `invoices()->cancel($id)` and every
  other bodyless mutation sent `[]` where Asaas declares an object schema. The
  connector now routes JSON bodies through `Support\JsonBody`, which serializes
  an empty payload as `{}`.
- **An empty error body produced an exception with no message.**
  `ErrorEnvelope` trimmed the body to `''`, and the `?? 'Asaas API error'`
  default in `AsaasRequestException` only covers `null` — so a 502/504 from a
  proxy, gateway or WAF surfaced as an exception whose `getMessage()` was the
  empty string, contradicting the class's own contract. Such responses now
  describe themselves by status.
- **An object-shaped `errors` envelope broke `$errors[0]`.** The guard only
  checked `is_array()`, so `{"errors": {"code": …, "description": …}}` passed
  through annotated as a `non-empty-list` it was not, leaving
  `$result->errors[0]['description']` undefined. Non-list envelopes now fall back
  to the synthesized `UNKNOWN_ERROR` row.
- **`payments()->listDocuments()` could not be paged from the first call.** It
  hardcoded an empty query, the only `list*` method in the SDK without a
  `$query` parameter, so `limit`/`offset` were unreachable until `next()`.
- **The fake client could not be built outside Laravel.** `StubResponse` and
  three `FakeAsaasClient` stub helpers normalized stubs through the `Http`
  facade, while the fake deliberately builds its own `Factory` to stay
  container-free. In a plain-PHP suite — the standalone usage `README.md`
  documents — `AsaasClient::fake([...])` died in the constructor with
  `RuntimeException: A facade root has not been set.` Stub responses are now
  built via `Factory::response()` and `$factory->sequence()`, which need no
  facade root.
- **`stubPages()` stopped the walk after the first page.** Each page was
  normalized in isolation, and the envelope inference marks a page it cannot
  see past as `hasMore=false`, so `next()` returned `null` and `->all()` yielded
  only the first page's rows — silently, indistinguishable from a complete walk.
  The only multi-page test declared `hasMore` by hand and never exercised the
  inference. Inference now runs over the whole sequence: every page but the last
  gets `hasMore=true`, and `totalCount` counts the rows of all pages.
- **`assertSent()` silently dropped its second closure.** `assertSent(fn ($r) =>
  ..., fn ($r) => ...)` type-checks, but only the first predicate was ever
  applied — the second was discarded without a warning, turning the call into an
  assertion that can never fail (a payload check written that way passed against
  any payload). Both `assertSent()` and `assertNotSent()` now throw
  `InvalidArgumentException` when handed two closures.
- **`AsaasClient::for()` escaped `Http::fake()` inside Laravel.** The standalone
  factory built a bare `PendingRequest`, which carries its own Guzzle client and
  therefore answers to neither `Http::fake()` nor
  `Http::preventStrayRequests()`. Since `AsaasClient::for()` is the documented
  way to hold a per-tenant API key, a Laravel suite that faked HTTP still issued
  live calls to Asaas with real credentials. It now routes through the
  framework's HTTP factory whenever one is bound, and falls back to the detached
  request outside Laravel (new `Support\PendingRequestFactory`).
- **`MyAccountResource::uploadDocumentFile()` posted to a route that does not
  exist.** It built `POST /v3/myAccount/documents/{id}/files`; the endpoint is
  `POST /v3/myAccount/documents/{id}` (`specs/domains/my-account-documents.json`
  declares only `/documents`, `/documents/{id}` and `/documents/files/{id}` —
  the `/files` suffix exists on the file triplet, not on the upload route). The
  multipart body was already correct, so every white-label KYC document upload
  failed on the URL alone and subaccount onboarding could not be completed
  through the SDK. `README.md` already documented the correct path.
- **The `Asaas` facade escaped the documented fake swap.** The facade accessor
  returned the concrete `AsaasClient` while the container seam is
  `alias(AsaasClient::class, AsaasClientContract::class)`. `Container::instance()`
  unsets the alias it overrides, so
  `$this->app->instance(AsaasClientContract::class, AsaasClient::fake())` — the
  recipe in `README.md` — detached the alias and left the `AsaasClient` binding
  untouched: injected code saw the fake while `Asaas::payments()` still resolved
  the real client and issued live HTTP with the configured API key. The accessor
  now resolves `AsaasClientContract`, so one override covers both surfaces.
- **`all()` skipped rows and truncated silently.** `AsaasPaginatedResult::next()`
  computed the next offset as `offset + limit` from the response envelope. A page
  returning fewer rows than its limit skipped the difference (a 100-row limit
  answered with 10 rows jumped straight to offset 100), and an envelope omitting
  `limit` fell into the `limit < 1` guard, ending the walk while `hasMore` still
  said otherwise — with no error, indistinguishable from a clean end-of-list. The
  cursor now advances by the number of rows actually delivered and terminates on
  an empty page, which is the case that genuinely cannot make progress.
- **`CreateInvoiceRequest::fromArray()` and `BankAccount::fromArray()` rejected
  nested DTOs.** Both called `Taxes::fromArray()` / `Bank::fromArray()` eagerly
  instead of handing the raw value to their constructor, which already coerces
  `array|DTO`. Passing a typed DTO inside an array payload — e.g.
  `invoices()->create(['taxes' => new Taxes(...)])` or
  `transfers()->create(['bankAccount' => ['bank' => new Bank('001')]])` — raised
  `TypeError` before any request was sent, breaking the array-or-DTO contract
  documented in `README.md`.
- **Transport-failure stubs recorded nothing, making assertions vacuous.**
  `stubRequestNotDelivered()` / `stubIndeterminateResult()` threw an Illuminate
  `ConnectionException` straight from the stub closure, bypassing
  `PendingRequest::marshalConnectionException()` — the only place Laravel records
  the request/response pair. The request never reached `recorded()`, so
  `assertNotSent()` and `assertNothingSent()` passed regardless of what the code
  under test did, on exactly the failure path those tests exist to pin. The stubs
  now raise the Guzzle `ConnectException` with the real PSR request, so Laravel
  marshals it like a live cURL error: the pair is recorded with a `null` response
  before the Illuminate exception is raised.
- **`StubResponse` dropped unknown top-level keys.** Pagination inference
  rebuilt the body from a fixed key set, so stubbing a realistic envelope with
  an extra field the code under test reads silently lost it — as did a non-list
  body that merely happened to carry a list under `data`. The inferred defaults
  are now merged *under* the caller's body.
- **`IdGuard::validate()` accepted a trailing newline.** PCRE's `$` also matches
  just before a final `\n`, so an untrimmed id (`"pay_123\n"`, e.g. read from a
  file without `trim()`) passed the guard and reached the URL percent-encoded as
  `pay_123%0A`, turning the intended `InvalidArgumentException` into a wasted
  round-trip and an HTTP 404. Anchored with the `D` modifier.
- **`PaymentCheckoutConfigRequest` and `UpdatePaymentDocumentRequest` read
  required keys without a guard.** A missing key emitted `Undefined array key`
  plus a `TypeError` instead of the descriptive `InvalidArgumentException` every
  other DTO produces — and under Laravel's `HandleExceptions` the warning
  surfaces first as an `ErrorException`, so callers saw a third, unrelated
  exception class. Both now guard like the rest of the package.
- **The recorded request/response pair was typed as non-nullable.**
  `FakeAsaasClient::recorded()` and the `RecordsHttpAssertions` helpers declared
  `list<array{0: Request, 1: Response}>`, but Laravel records `[$request, null]`
  for a request that failed in transport — exactly what `stubRequestNotDelivered()`
  and `stubIndeterminateResult()` produce, and what `README.md` advertises. A
  matcher closure written against that type (`fn (Request $r, Response $resp)`)
  raised `TypeError: Argument #2 ($resp) must be of type Response, null given`,
  and static analysis blessed `$entry[1]->status()` on a value that can be null.
  The type is now `?Response` everywhere, with the nullability documented on the
  trait and in `README.md`.
- **`finishEscrow()` invited the wrong identifier.** Its parameter was `$id`,
  sitting one line below `getEscrow(string $id)` in both the class and
  `README.md`, so the obvious reading was "same id, two calls". It is not:
  `GET /v3/payments/{id}/escrow` takes the payment id and *returns* the
  guarantee id, while `POST /v3/escrow/{id}/finish` takes that guarantee id — a
  UUID, per `specs/domains/escrow.json`. Feeding it a `pay_…` id produced a 404
  that `IdGuard` cannot catch, since a UUID and a payment id share the same
  charset. The parameter is now `$escrowId`, documented on the method and in
  `README.md`, and the test that froze the wrong semantic now pins the
  guarantee id.
- **`specs/concept-fields.md` described a `refundBankSlip()` signature and body
  that do not exist.** It declared
  `refundBankSlip(string $id, array|RefundPaymentRequest $data = [])` with a
  body of `value?` / `description?` / `refundOnCustomerCreditCard?`, "identical
  to the credit-card refund". `https://docs.asaas.com/reference/estornar-boleto`
  says the opposite — *"Este endpoint não exige envio de parâmetros no corpo da
  requisição"* — and the endpoint is a different concept entirely: it starts a
  customer-driven flow and answers with a single `requestUrl`. The field
  `refundOnCustomerCreditCard` exists on neither endpoint. Since
  `concept-fields.md` is normative for wire surface absent from the OpenAPI
  export, the entry was steering the SDK toward an endpoint that is not there;
  it now matches the docs, and the reasoning is pinned on the method and by an
  empty-body assertion in the test.
- **`UpdateInvoiceRequest` sent `municipalServiceName`, which the update
  endpoint does not accept.** `PUT /v3/invoices/{id}` accepts exactly
  `serviceDescription`, `observations`, `externalReference`, `value`,
  `deductions`, `effectiveDate`, `updatePayment` and `taxes`
  (`specs/domains/invoices.json`, confirmed against
  https://docs.asaas.com/reference/atualizar-nota-fiscal); `municipalServiceName`
  exists only on `POST /v3/invoices`, and no `concept-fields.md` entry covers it,
  so by the spec-doc-authority rule it does not belong on the update DTO. The
  field is dropped from the DTO; passing the key in an array is now ignored
  instead of being put on the wire. `CreateInvoiceRequest` is untouched.
  Classified as a fix rather than a breaking change: setting it never changed
  anything about an invoice, so no caller could depend on its behaviour. Code
  that passes it as a named argument (`new UpdateInvoiceRequest(municipalServiceName: …)`),
  passes six or more positional arguments, or reads the property will fail
  loudly and should simply drop the field. (The commit that introduced this,
  `ad10b54`, is worded as a breaking change; this entry supersedes that
  classification — see the note under Changed.)
- **`PixKeyRequest` accepted key types the create endpoint cannot mint.** It
  took the full `PixAddressKeyType` enum, so `new PixKeyRequest(PixAddressKeyType::Cpf)`
  type-checked, serialized to `"CPF"` and earned a remote HTTP 400.
  `POST /v3/pix/addressKeys` declares `"enum": ["EVP"]` — Asaas only mints random
  keys through the API, and CPF/CNPJ/EMAIL/PHONE keys are registered in the panel
  (`specs/domains/pix.json`, confirmed against
  https://docs.asaas.com/reference/criar-uma-chave). The DTO now rejects anything
  but `EVP` with an `InvalidArgumentException` at construction. The enum itself is
  unchanged: `TransferRequest::$pixAddressKeyType` legitimately accepts all five.
- **Every multipart upload after the first shipped a JSON content type.**
  `AsaasConnector::postMultipart()` restored the body format with `asJson()`,
  which also pins an explicit `Content-Type: application/json` header — and
  Guzzle supplies its own `multipart/form-data; boundary=...` only when no such
  header is already set. The first call escaped it because
  `PendingRequest::__construct` overwrites `options` after its own `asJson()`;
  from the second call on, the boundary was gone and the multipart body arrived
  mislabelled and unparseable. In Laravel `AsaasClient` is a container
  singleton, so two document uploads in one request/worker were enough to hit
  it, across all five upload routes (payment receipts, fiscal-info logos,
  subaccount KYC documents). The body format is now restored with
  `bodyFormat('json')`, which Guzzle already labels correctly on its own.
- **`stubPages()` skipped pagination inference.** It pushed raw page arrays into
  the sequence instead of routing them through `StubResponse::normalize()` the
  way `stub()` and the constructor do. A page written as `['data' => [...]]` —
  the self-describing shape `README.md` documents — came back with `totalCount`
  and `limit` at 0, so paging assertions failed for a reason that had nothing to
  do with the code under test. On a lone `stub()`, declaring `hasMore` or
  `totalCount` still disables inference, as documented.
- **A page declaring `totalCount` stopped the `stubPages()` walk.** The
  "caller declared pagination, hands off" rule lived in the inference helper
  shared by `stub()` and `stubPages()`, so a page volunteering `totalCount` (or
  `hasMore`) also suppressed the `hasMore` that only the full sequence can know.
  `stubPages([['data' => [$a], 'totalCount' => 2], ['data' => [$b]]])` ended the
  walk after page one: `->all()` yielded `[$a]` and looked like a complete
  result. The rule now applies to `stub()` only, where there is no sequence to
  reason about; `stubPages()` decides `hasMore` itself — see *Breaking* — and
  still lets each page keep every other pagination key it declares.
- **A scalar inside the `errors` list broke `$errors[0]['description']`.**
  Widening the guard to reject object-shaped envelopes left the sibling case
  open: `{"errors": ["boom"]}` is a list, so it passed through annotated as a
  `list<array{…}>` it was not, and the documented read
  `$result->errors[0]['description']` raised `TypeError: Cannot access offset of
  type string on string` — an uncaught throw out of the Result-based contract.
  Every item is now checked, and a list carrying scalars falls back to the
  synthesized `UNKNOWN_ERROR` row like any other non-canonical envelope.
- **A markup-only error body produced a blank exception message.** `describe()`
  tested the stripped body for emptiness without trimming, so a proxy or WAF page
  with no text left `strip_tags()` returning whitespace — non-empty, hence
  preferred over the status fallback. `AsaasRequestException::getMessage()` came
  back as a run of spaces, the same "nothing to act on in the log" failure the
  empty-body fix above was meant to close. The body is now trimmed before the
  check.
- **An error row shaped as a list was passed through as if it were an object.**
  `{"errors": [[1, 2]]}` survived the `is_array()` check and reached the caller
  annotated as carrying `code`/`description`, which it has no way to. Unreadable
  rows are now dropped, and the envelope falls back to the synthesized row only
  when *nothing* in the list can be read as an error object — discarding a
  readable `invalid_cpfCnpj` because a sibling entry is junk would replace the
  diagnosis the caller needed with a dump of the body. `{}` — which decodes to
  the same PHP value as `[]` — is still a canonical row and is still passed
  through; `AsaasRequestException` substitutes its own message for it.
- **The terminal page of a lone `hasMore: true` stub dropped the envelope the
  stub declared.** It answered with a fixed `object: 'list'` and discarded any
  extra top-level field, so a test describing a different envelope got a page
  its own assertions could not recognise — the opposite of the rule
  `stubPages()` follows. It now keeps the stub's envelope and replaces only the
  walk-position keys.
- **A stub modelling a later page answered with an empty one.** A lone `stub()`
  declaring `hasMore: true` served its body only at offset 0, so a stub written
  as page two (`'offset' => 10`) answered `->list(['offset' => 10])` with the
  empty terminal page — a page the test never described. The cut is now the
  offset the stub declares, and a request carrying no `offset` at all still gets
  the body. The anti-loop guarantee is unchanged: `next()` always asks for a
  different, explicit offset.
- **A `v3/`-prefixed pattern made `assertNotSent()` incapable of failing.** The
  base URL already ends in `/v3`, so `assertNotSent('v3/payments/pay_1')` — the
  shape docs.asaas.com uses for every endpoint — resolved to
  `…/v3/v3/payments/pay_1*`, matched nothing, and passed however many such
  requests the code under test had sent. Same defect the absolute-URL guard
  closed, different trigger; the guard now covers both.
- **A canonical `description` that was not a string escaped the Result
  contract.** `ErrorEnvelope` passes canonical rows through verbatim, so
  `{"errors":[{"description":123}]}` reached `AsaasRequestException`, whose
  parent constructor requires a string: `orFail()` raised `TypeError` rather
  than the SDK's own exception. The empty-string fallback now covers every
  non-string value too.
- **`all()` could iterate forever on an endpoint that ignored `offset`.** The
  walk stopped only on an empty page, so a server answering every request with
  the same non-empty page and `hasMore: true` produced an infinite generator
  emitting duplicates. This is a live possibility on the routes whose query
  parameters Asaas never documented — `GET /v3/payments/{id}/refunds` among them,
  which `allRefunds()` now walks. The walk also stops once the envelope's own
  `totalCount` has been delivered — every domain spec defines that field as
  "quantidade total de itens para os filtros informados", the whole filtered set
  rather than the page. When that brake fires while the same response still says
  `hasMore: true`, the endpoint is contradicting itself and rows may be missing,
  so the generator yields a final `AsaasPaginatedError` with code
  `PAGINATION_INCONSISTENT` instead of stopping quietly — a silent stop there
  would be indistinguishable from a complete walk, which is the failure this
  backstop sits beside. `specs/concept-fields.md` records the refunds
  query-parameter gap and flags it as inferred from the response envelope rather
  than read off a doc page.
- **`all()` handed the caller the same row several times before stopping, and
  did not stop at all without `totalCount`.** The backstop above only fires once
  as many rows as the whole filtered set have been delivered; on a page shorter
  than that set, those rows are the *same* rows handed over repeatedly, so a
  consumer summing a `value` field had already double-counted by the time the
  error arrived. An envelope omitting `totalCount` reports `0` and never reached
  the backstop at all, leaving that walk unbounded. The walk now also stops when
  a page carries exactly the rows of the page before it — `next()` always
  advances the cursor by the rows just delivered, so a page repeating the one
  before it *while still saying `hasMore: true`* means the endpoint disregarded
  the offset it was sent. The check runs *before* the rows are yielded, so no
  duplicate reaches the caller, and it yields a final `AsaasPaginatedError` with
  the new code `PAGINATION_STALLED`. Two qualifiers carry weight: the pages must
  be *consecutive* (a row set reappearing after a different page in between is a
  real walk), and the repeat must still promise another page — a page saying the
  walk is over ends it either way, and a sequence of fixtures in `stubPages()`
  produces exactly the shape a stalled endpoint does.

### Changed

- **BREAKING: `payments()->listRefunds()` now returns `AsaasPaginatedResult`.**
  `GET /v3/payments/{id}/refunds` answers with the standard pagination envelope
  (`specs/domains/payment-refunds.json` declares `object`, `hasMore`,
  `totalCount`, `limit`, `offset`, `data`), but the method used `get()` and
  handed back a flat `AsaasResult`. It was the only `get()` in the SDK pointed at
  a paginated endpoint. A payment with more refunds than the page limit silently
  returned only the first page, with no `next()`, `hasMore` or `totalCount` to
  notice it by. It now takes an optional `$query` and returns
  `AsaasPaginatedResult`; `allRefunds()` was added to walk every page. Callers
  reading `$result->data` keep working — the rows are still there. The endpoint's
  request-side query parameters are undocumented upstream; see the
  infinite-walk entry under *Fixed* and the `specs/concept-fields.md` record.
- `AsaasPaginatedResult::$offset` now reports the offset the SDK requested for
  that page rather than the one echoed by the server. The two agree whenever the
  API echoes what it was asked for; the requested value is used because it is
  always present, whereas an envelope omitting `offset` would pin the cursor at
  `0`. Only visible if you assert on `$result->offset` for a page whose envelope
  disagreed with the request.
- `AsaasPaginatedResult::next()` no longer returns `null` when the envelope
  reports `limit < 1`; it returns `null` on an empty page instead. Callers
  driving pagination manually via `next()` now keep walking through envelopes
  that omit `limit`.
- `FakeTransportFailure` (test-support class) now exposes
  `notDeliveredErrno(phase)`, `indeterminateErrno(phase)` and
  `connectException(errno, request)` in place of `requestNotDelivered(phase)` /
  `indeterminateResult(phase)`, which returned a ready-made Illuminate
  `ConnectionException`. The errno lookups are separate from the exception
  builder so a phase can still be validated eagerly at stub-registration time
  while construction waits for the actual request. `stubRequestNotDelivered()`
  and `stubIndeterminateResult()` are unchanged — this only affects code calling
  `FakeTransportFailure` directly.
- `Asaas` facade `@mixin` points at `AsaasClientContract` instead of
  `AsaasClient`. The two surfaces are identical apart from `__debugInfo()`,
  which is never reached through a facade call.

> **Release note — on the `municipalServiceName` classification.** Commit
> `ad10b54` is worded `fix(invoices)!` with a `BREAKING CHANGE:` footer. Taken
> alone that field's removal is arguably not breaking — it never had any effect
> on an update, so no caller could depend on its behaviour — but the point is
> moot: the *Breaking* section above carries several changes that are breaking
> on their own terms, so this release is a **major** either way.

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

[Unreleased]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.4.0...v2.0.0
[1.4.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/OwnerPro-Software/asaas-php-sdk/releases/tag/v1.0.0
