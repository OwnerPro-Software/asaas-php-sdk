# CLAUDE.md

## Project Overview

PHP SDK for the [Asaas](https://www.asaas.com/) payment platform API. Works both as a Laravel package (auto-discovered `ServiceProvider` + `Asaas` facade) and as a standalone library in plain PHP via `AsaasClient::for(apiKey: ...)`. Target: PHP 8.2+, `illuminate/http` 10/11/12.

The public, user-facing contract is documented in `README.md` — consult it before changing any public API surface and update it whenever methods, parameters, or behavior change.

> **Never commit anything under `docs/`.** That directory is reserved for internal planning artifacts (specs, plans, scratch notes) and is gitignored. If a doc belongs in the repo, it goes in `README.md`, `CHANGELOG.md`, or another top-level file — not under `docs/`.

---

## Architecture

Top-down map of the moving parts:

- **`AsaasClient`** (`src/AsaasClient.php`) — entry point. Lazy-resolves one Resource per API domain (`payments()`, `pix()`, `transfers()`, `webhooks()`, `invoices()`, `accounts()`, `creditCards()`, `billPayments()`, `statements()`, `pixTransactions()`). Constructed via `AsaasClient::for()` for standalone usage, or via the Laravel container/`Asaas` facade.
- **Resource classes** (`src/<Domain>/<Domain>Resource.php`) — one per API domain. Thin orchestrators that translate method calls into `Connector` HTTP calls. Each Resource holds a `private const BASE` path constant and a `private function path(string $id, string $suffix = '')` helper.
- **`Connector` interface + `AsaasConnector`** (`src/Support/`) — Humble Object wrapping `Illuminate\Http\Client\PendingRequest`. Exposes `get/post/put/delete/paginate/all`. Handles `ConnectionException`, error extraction, and result wrapping. Built via `AsaasConnector::forStandalone()` or `AsaasConnector::forLaravel()`.
- **`AsaasResult` / `AsaasPaginatedResult`** (`src/Support/`) — Result objects returned by every Resource method. No exceptions by default: `$result->success`, `$result->data`, `$result->errors`, `$result->response`. Use the `ThrowsOnFailure` trait opt-in when exception semantics are needed.
- **`Environment` enum** (`src/Support/Environment.php`) — `Sandbox` / `Production`. `baseUrl()` **already includes the `/v3` suffix** — never prefix paths with `/v3` inside Resources.
- **Request DTOs** (`src/<Domain>/Request/*.php`) — typed request payloads. Use the `HasArrayFactory` trait (or `HasUpdatableArrayFactory` for partial updates).
- **Shared DTOs** (`src/Support/DTO/*.php`) — reusable value objects (`Split`, `Callback`, `CreditCard`, `Bank`, `BankAccount`, `Taxes`, `QrCodePayload`, etc.).

---

## Key Patterns

- **Result-based error handling** — methods never throw on API failures by default. They return `AsaasResult` with `success`/`failure` factory methods. This is intentional; do not wrap calls in try/catch unless you've opted into `ThrowsOnFailure`.
- **`array|RequestDTO` on resource methods** — every mutation method accepts either a typed DTO or a raw array, normalized via `RequestDTO::resolveData($data)` (from `HasArrayFactory`). Keep both shapes supported when adding new endpoints.
- **Eager DTO coercion in constructors** — when a Request DTO has nested DTOs (e.g. `CreatePaymentRequest` → `Split`, `Callback`, `CreditCard`), the constructor coerces raw arrays to DTO instances immediately for eager validation. Follow this pattern for any new nested-DTO fields.
- **`IdGuard::validate()` in every `path()`** — URL-segment IDs must go through `IdGuard::validate($id)` to reject empty/malformed IDs before the HTTP call. Never concatenate raw `$id` into a URL.
- **Private `path()` helper per Resource** — resources use `$this->path($id, '/suffix')` instead of inlining `sprintf('%s/%s/suffix', self::BASE, $id)` — this is an intentional refactor to reduce duplication. Keep it.
- **Enum policy: requests only** — enums like `BillingType`, `WebhookEvent` are accepted on request DTOs, but response data stays as raw strings for backward compatibility. Do not convert response fields to enums.
- **DTO naming — drop the action prefix for single-action requests** — e.g., if a resource has only one update request, name it after the entity, not `Update<Entity>Request`. For multi-action resources (like `Payment`), keep the action prefix.
- **`WebhookVerifier` IPs are configurable** — pass allowed IPs via constructor, don't hardcode.

---

## Testing

- Built on **Pest 4** + **Orchestra Testbench** — `tests/TestCase.php` extends Testbench and registers the `AsaasServiceProvider` with sandbox defaults.
- `tests/Pest.php` applies `TestCase` globally to all test files in `tests/` — no need to declare `uses(TestCase::class)` per file.
- A shared `error_fixture` dataset is declared in `tests/Pest.php`, loading `tests/Fixtures/error_400.json`.
- Test organization mirrors `src/` — one test folder per domain.
- Mutation testing is required (`--min=100`); any new code must survive mutation or be covered by targeted tests.
- **Mutation testing happens at the END of a feature, never mid-implementation.** Per-task: aim for 100% line coverage with regular tests and move on. Run `--mutate` once, in a dedicated batch task, after the feature lands. Reasons:
    - Per-task mutation runs burn cycles on cache-trap thrash and surface ordering-dependent escapes that disappear once the full suite is in place.
    - Some mutations are equivalent and can only be classified as such with the full feature in view — chasing them per-task leads to over-engineered tests for code that hasn't stabilised.
    - When the batch run surfaces escapes, fix them by adding behaviour-pinning tests OR by refactoring the source to remove the mutation surface (e.g., delete a redundant ternary). Never weaken assertions to make a mutation pass.
- **Pest mutate cache trap:** `vendor/pestphp/pest-plugin-mutate/.temp/mutations/` caches kill-results per source-file hash. If a mutation was killed in a previous run and the source line hasn't changed, the plugin reuses the cached pass — even if the test that used to cover it has weakened. CI runs without this cache and surfaces escapes that look green locally. Always invalidate the cache via `--clear-cache` (the plugin's official flag) before trusting a local mutation run; do not `rm -rf` the directory because the plugin tries to write cache during the run and `file_put_contents: No such directory` warnings can leave the run reporting a partial mutant set as 100%. Also watch for `assertSent` callbacks that early-`return true` on unrelated requests (those make the assertion always pass).

---

## Quality Checks

Always run before considering the work done:

```bash
# tests

./vendor/bin/pest --coverage --min=100 --parallel # runs the complete suite test
# mutation testing — run ONLY at the end of a feature, never per-implementation-step
./vendor/bin/pest --mutate --clear-cache --min=100 --parallel # mutation tests (--clear-cache invalidates the stale-cache trap)

# quality checks

./vendor/bin/pest --type-coverage --min=100 # runs type coverage tests
./vendor/bin/rector --dry-run # runs rector quality checks
./vendor/bin/phpstan analyse # run static analysis check
./vendor/bin/psalm --taint-analysis # security analysis
./vendor/bin/pint -p # runs the pint format rules

# if any quality checks changed any file, full suite need to be run again!
./vendor/bin/pest --coverage --min=100 --parallel # runs the complete suite test
# mutation
# type-coverage
# rector
# phpstan
# psalm
# pint
```

Type coverage must remain at 100%. Any new or changed code must include complete type hints.

## Documentation

Whenever the public API changes (new methods, renamed parameters, changed behavior), update `README.md` to reflect those changes.

---

## Specs

The Asaas wire contract is captured under `specs/`:

- `specs/asaas_openapi.json` — upstream OpenAPI export (full, source of truth for the split files).
- `specs/domains/*.json` — per-domain splits derived from the export.
- `specs/concept-fields.md` — cross-cutting fields/endpoints the SDK supports that are absent from the OpenAPI export but documented on `docs.asaas.com` (e.g. `accessTokenConfig`, `permissions[]`, `remoteIp` on `payWithCreditCard`). Each entry cites the canonical doc URL.

**Spec-doc-authority rule:** if a field/endpoint is **not** in `specs/domains/*.json`, **not** in `specs/concept-fields.md`, **and** not in `docs.asaas.com`, it does not belong in the SDK.

When auditing for wire-undocumented surface, consult **all three** before classifying — never just the OpenAPI split. When a future spec refresh adds a `concept-fields.md` entry to the OpenAPI export, delete the entry from `concept-fields.md` in the same commit. Slugs on `docs.asaas.com` frequently contain diacritics (e.g. `/docs/gerenciamento-de-permissões-de-chaves-de-api`); URL-encode them when fetching, and use the docs site-search as a fallback when slug-guessing fails.

---

## Clean Architecture Principles (Uncle Bob)

### SOLID

#### SRP - Single Responsibility Principle
- Each class must have **one single reason to change**
- Separate concerns: a class should not fetch data, validate, and format at the same time
- Controllers/Services/Repositories must have distinct roles

#### OCP - Open/Closed Principle
- Classes should be **open for extension, closed for modification**
- Use interfaces and abstractions to allow new behaviors without changing existing code
- Prefer composition and dependency injection over direct modification

#### LSP - Liskov Substitution Principle
- Subclasses must be **substitutable** for their base classes without breaking behavior
- Do not throw unexpected exceptions in interface implementations
- Respect contracts: preconditions cannot be more restrictive, postconditions cannot be more permissive

#### ISP - Interface Segregation Principle
- Prefer **specific interfaces** over large, generic ones
- No class should be forced to implement methods it does not use
- Break large interfaces into smaller, focused ones

#### DIP - Dependency Inversion Principle
- High-level modules **must not depend** on low-level modules; both should depend on abstractions
- Use constructor dependency injection
- Depend on interfaces, not concrete implementations

---

### Functions

- **Small**: functions should be short (ideally < 20 lines)
- **Do one thing**: if a function does more than one thing, extract into subfunctions
- **Few parameters**: ideally 0-2, maximum 3; beyond that, consider an object/DTO
- **No side effects**: functions should not cause unexpected state changes
- **Command-Query Separation**: functions either return something (query) or change state (command), never both

---

### Naming

- **Reveal intention**: the name should tell what the variable/function/class does, without needing a comment
- **Avoid disinformation**: do not use names that can be confused with other concepts
- **Make meaningful distinctions**: `$data1` and `$data2` say nothing; use descriptive names
- **Pronounceable and searchable names**: avoid obscure abbreviations
- **Classes = nouns**, Methods = verbs: `Invoice`, `Company` vs `calculate()`, `send()`

---

### Comments

- **Self-explanatory code** is preferable to comments
- Valid comments: intention, clarification, warning of consequences, TODO
- Bad comments: redundant, misleading, commented-out code, journaling
- **Never** leave commented-out code in the repository; use git for history

---

### Error Handling

- **Use exceptions**, not return codes
- Create domain-specific exceptions when needed
- **Do not return null**: use Null Object Pattern, Optional, or throw an exception
- **Do not pass null** as an argument
- Handle errors at the appropriate level; do not silently swallow exceptions

---

### Formatting

- **Consistency** above all
- Related functions should be placed close together vertically
- Variables declared close to their usage
- Follow PSR-12 for PHP

---

### DRY - Don't Repeat Yourself

- Every piece of knowledge must have a **single, unambiguous representation** in the system
- Abstract duplications into functions, classes, or traits
- But: do not abstract prematurely; duplication is better than the wrong abstraction

---

### Law of Demeter

- An object should only talk to its **immediate neighbors**
- Avoid chains like `$this->getA()->getB()->getC()->doSomething()`
- Each unit should have limited knowledge about other units

---

### Boy Scout Rule

- **Leave the code cleaner than you found it**
- Small continuous improvements prevent degradation

---

### YAGNI - You Aren't Gonna Need It

- **Do not implement** features that are not needed right now
- Solve the current problem in the simplest way possible
- Additional complexity only when justified by real requirements

---

### KISS - Keep It Simple, Stupid

- The simplest solution that works is the best
- Avoid over-engineering and unnecessary abstractions
- Simple code is easier to understand, test, and maintain

---

### Composition over Inheritance

- Prefer **composition** (has-a) over inheritance (is-a)
- Inheritance creates tight coupling between classes
- Use traits sparingly and with clear purpose

---

### Clean Architecture - Dependency Rule

- Dependencies always point **inward** (domain at the center)
- Layers: Entities > Use Cases > Interface Adapters > Frameworks
- The domain **never** depends on frameworks, databases, or external details
- Frameworks are implementation details, not the architecture

---

### Principle of Least Astonishment (POLA)

- Code should behave **as the reader expects** — no surprises
- If a method named `save()` also sends an email, that violates POLA
- Follow conventions: if the codebase does something one way, do it the same way everywhere
- Unexpected side effects are bugs waiting to happen

---

### Single Level of Abstraction (SLA)

- Each function should operate at **one single level of abstraction**
- Do not mix high-level orchestration with low-level details in the same function
- If a function calls `$this->validateOrder()` and then does `strlen($input) > 0`, it mixes abstraction levels
- Extract low-level details into well-named helper functions

---

### Tell, Don't Ask

- **Tell objects what to do**, do not ask for their state to decide for them
- Prefer `$order->complete()` over `if ($order->getStatus() === 'paid') { $order->setStatus('completed'); }`
- Asking for state and making decisions externally breaks encapsulation and scatters logic
- Related to Law of Demeter but distinct: Demeter limits who you talk to, Tell Don't Ask limits *how* you talk

---

### Humble Object Pattern

- Separate **testable logic** from code that is hard to test (I/O, frameworks, UI)
- The "humble object" is a thin wrapper containing only the hard-to-test code (HTTP, database, filesystem)
- The real logic lives in plain objects that are **easy to unit test** without mocks or infrastructure
- In Laravel: keep controllers humble (thin), push logic into Actions/Services that don't depend on the framework

---

### Immutability

- Prefer **immutable objects** when possible — once created, they do not change
- Value Objects should always be immutable: a `Money(100, 'BRL')` never changes; you create a new one
- Immutability eliminates bugs from unexpected state changes and makes code easier to reason about
- Use `readonly` properties in PHP 8.2+ to enforce immutability at the language level

---

### Polymorphism over Conditionals

- Replace long **if/else or switch chains** with polymorphism
- Each condition branch becomes a class that implements a common interface
- Adding a new behavior means adding a new class, not modifying existing conditionals (respects OCP)
- Use the **Strategy Pattern** to select the right implementation at runtime

---

### Screaming Architecture

- The project structure should **scream its purpose** — looking at the directory layout should reveal the domain, not the framework
- A healthcare system should look like a healthcare system, not "a Laravel project"
- Organize by **domain concepts** (Invoices, Companies, Products), not by technical layers alone (Controllers, Models, Services)
- Frameworks are delivery mechanisms, not the architecture — they should be hidden behind boundaries
- If a new developer looks at the folder structure and says "this is a Laravel app" instead of "this is a fiscal document system", the architecture is not screaming loud enough

---

### Boundaries

- Wrap third-party code behind **your own interfaces** — never let external APIs leak into your domain
- Use the **Adapter pattern** to translate between external data formats and your internal models
- When the external API changes, only the adapter needs to change — the rest of your codebase is protected
- Write **boundary tests** (integration/contract tests) that verify your assumptions about the external API
- This package (Asaas PHP SDK) *is* a boundary: it isolates applications from Asaas API details

---

### Classes

- Keep classes **small** — measured by responsibilities, not lines of code
- High **cohesion**: every method in a class should use most of the class's instance variables
- If a subset of methods only uses a subset of variables, that's a sign the class should be split
- Avoid **God classes** that know too much or do too much
- A class with too many dependencies (constructor parameters) likely has too many responsibilities

---

### Stepdown Rule / Newspaper Metaphor

- Code should read **top-to-bottom** like a newspaper article
- High-level functions first, implementation details below
- Callers appear above callees
- Each function should lead naturally to the next level of abstraction
- A reader should be able to understand the "what" without scrolling to the "how"

---

### Four Rules of Simple Design (Kent Beck)

In order of priority:

1. **Passes all tests** — correctness is non-negotiable
2. **Reveals intention** — code clearly communicates what it does and why
3. **No duplication** — every piece of knowledge exists in one place (DRY)
4. **Fewest elements** — remove anything that does not serve the first three rules

---

### Successive Refinement

- **First make it work, then make it clean**
- Do not try to write perfect code on the first pass
- Write the working solution, then refactor: extract methods, rename variables, simplify logic
- Clean code is not written — it is **refined**

---

### Data/Object Anti-Symmetry

- **Objects** hide data and expose behavior (encapsulation)
- **Data structures** expose data and have no meaningful behavior (DTOs, arrays)
- Do not mix the two: a class that exposes all its fields *and* has business methods violates both paradigms
- Use DTOs for data transfer, objects for behavior

---

### Code Smells

Watch out for these warning signs:

- **Long Method** — function does too much; extract smaller functions
- **Large Class** — class has too many responsibilities; split it
- **Feature Envy** — a method uses another class's data more than its own; move it
- **Data Clumps** — the same group of parameters appears together repeatedly; extract into an object/DTO
- **Divergent Change** — one class is changed for multiple unrelated reasons; split by responsibility
- **Shotgun Surgery** — one change requires edits across many classes; consolidate related logic
- **Primitive Obsession** — using primitives instead of small value objects (e.g., string for email, CNPJ)
- **Switch Statements** — long switch/if-else chains; consider polymorphism or strategy pattern
- **Dead Code** — unused code; delete it, git remembers

---

## Review Checklist

Before finalizing any code, verify:

1. Does each class have a single responsibility?
2. Are functions small and do one thing only?
3. Are names clear and reveal intention?
4. Is there duplicated code that could be extracted?
5. Does error handling use exceptions properly?
6. Do dependencies point in the correct direction?
7. Do tests cover the critical scenarios?
8. Is the code simple enough?

---

## Testing Principles (Uncle Bob / Clean Code)

### F.I.R.S.T.

- **Fast** — Tests must run quickly. Slow tests don't get run.
- **Independent** — Tests must not depend on each other. Any test should run alone or in any order.
- **Repeatable** — Tests must produce the same result in any environment, every time.
- **Self-Validating** — Tests either pass or fail. No manual inspection of logs or output.
- **Timely** — Write tests at the right time (ideally before the code, TDD).

### Test the Public API, Not Implementation Details

- Tests should exercise the public interface of the system under test.
- Never test private methods or internal state directly — if the behavior matters, it's observable through the public API.
- Reflection in tests is a code smell. Exception: verifying safety-critical invariants (e.g., SSL enforcement in production) where the behavior cannot be observed otherwise.
- If something is hard to test through the public API, the design likely needs to change, not the test.

### One Assertion Per Concept

- Each test should verify one logical concept.
- Multiple asserts are fine when they all verify the same behavior from different angles.
- Avoid testing unrelated things in the same test.

### Arrange-Act-Assert (AAA)

- **Arrange** — Set up the preconditions and inputs.
- **Act** — Execute the behavior under test.
- **Assert** — Verify the expected outcome.
- Keep these sections clearly separated.

### Clean Tests Are Readable

- Tests are documentation. A developer should understand the expected behavior by reading the test.
- Use descriptive test names that state the scenario and expected outcome.
- Minimize noise — use helpers/factories for setup, keep the test body focused on what's being verified.

### Don't Test the Framework

- Don't test that Laravel, Pest, or PHP itself works.
- Test *your* code and *your* business rules.

### Tests Should Be as Easy to Change as Production Code

- If changing an internal detail (renaming a private property, restructuring objects) breaks tests without changing behavior, the tests are coupled to implementation — fix them.
- Tests protect behavior, not structure.
