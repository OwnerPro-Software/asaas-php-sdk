<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\Account\MyAccountResource;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\Contracts\AsaasClientContract;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\FiscalInfo\FiscalInfoResource;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Payment\LeanPaymentResource;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticResource;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\TransportFailureClassifier;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;
use Throwable;

final class FakeAsaasClient implements AsaasClientContract
{
    use RecordsHttpAssertions;

    private readonly AsaasClient $asaasClient;

    private readonly Factory $factory;

    private readonly string $baseUrl;

    /** @var array<string, PromiseInterface|ResponseSequence|Closure> */
    private array $stubs = [];

    /** @param array<string, array<string, mixed>|PromiseInterface|ResponseSequence|Closure> $stubs */
    public function __construct(
        array $stubs = [],
        Environment|string $environment = Environment::Sandbox,
    ) {
        $this->baseUrl = ($environment instanceof Environment ? $environment : Environment::from($environment))->baseUrl();
        // The router answers or throws on every request, so nothing should ever
        // reach the real network. This says so to Laravel as well: should a
        // future change let a request slip past the router, it fails as a stray
        // request instead of quietly travelling to the live API.
        $this->factory = (new Factory)->preventStrayRequests();

        foreach ($stubs as $pattern => $stub) {
            $this->register($pattern, $stub);
        }

        $this->installRouter();
        $this->asaasClient = $this->buildClient();
    }

    public function payments(): PaymentResource
    {
        return $this->asaasClient->payments();
    }

    public function pix(): PixResource
    {
        return $this->asaasClient->pix();
    }

    public function pixTransactions(): PixTransactionResource
    {
        return $this->asaasClient->pixTransactions();
    }

    public function pixAutomatic(): PixAutomaticResource
    {
        return $this->asaasClient->pixAutomatic();
    }

    public function transfers(): TransferResource
    {
        return $this->asaasClient->transfers();
    }

    public function webhooks(): WebhookResource
    {
        return $this->asaasClient->webhooks();
    }

    public function invoices(): InvoiceResource
    {
        return $this->asaasClient->invoices();
    }

    public function accounts(): AccountResource
    {
        return $this->asaasClient->accounts();
    }

    public function myAccount(): MyAccountResource
    {
        return $this->asaasClient->myAccount();
    }

    public function creditCards(): CreditCardResource
    {
        return $this->asaasClient->creditCards();
    }

    public function billPayments(): BillPaymentResource
    {
        return $this->asaasClient->billPayments();
    }

    public function statements(): StatementResource
    {
        return $this->asaasClient->statements();
    }

    public function fiscalInfo(): FiscalInfoResource
    {
        return $this->asaasClient->fiscalInfo();
    }

    public function leanPayments(): LeanPaymentResource
    {
        return $this->asaasClient->leanPayments();
    }

    /** @param array<string, mixed>|PromiseInterface|ResponseSequence|Closure $stub */
    public function stub(string $pattern, array|PromiseInterface|ResponseSequence|Closure $stub): self
    {
        $this->register($pattern, $stub);

        return $this;
    }

    /**
     * Mirrors `Http::response($body, $status, $headers)` — the 4-positional
     * surface (pattern + body + status + headers) is a deliberate exception to
     * the "≤3 params" guideline because users already know this signature
     * shape from Laravel's HTTP client.
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function stubError(string $pattern, int $status, array $body = [], array $headers = []): self
    {
        $this->register($pattern, Factory::response($body, $status, $headers));

        return $this;
    }

    /**
     * Throws `$throwable` instead of returning a response.
     *
     * Whether the request stays visible to `assertSent`/`assertNotSent` depends
     * on what is thrown, because recording happens inside Laravel's handler
     * stack. `PendingRequest` marshals exactly two shapes —
     * `GuzzleHttp\Exception\ConnectException` and `RequestException` — and each
     * is recorded with a `null` response on the way past. Everything else,
     * including a plain `TransferException`, propagates before the recorder
     * runs and leaves no entry at all. Reach for `stubRequestNotDelivered()` /
     * `stubIndeterminateResult()` when the point is to model a transport
     * failure: they pick a shape that records.
     */
    public function stubException(string $pattern, Throwable $throwable): self
    {
        $this->register(
            $pattern,
            static function () use ($throwable): never {
                throw $throwable;
            },
        );

        return $this;
    }

    /**
     * Simulates a failure before any HTTP bytes reached the API (DNS, TCP
     * connect, TLS): the call throws `RequestNotDeliveredException`.
     *
     * @param  'connect'|'dns'|'tls'  $phase
     */
    public function stubRequestNotDelivered(string $pattern, string $phase = 'connect'): self
    {
        return $this->stubTransportErrno($pattern, FakeTransportFailure::notDeliveredErrno($phase));
    }

    /**
     * Simulates a failure after the request may have been processed (read
     * timeout, connection dropped mid-transfer, a 2xx with unreadable body for
     * `phase: 'body'`, or a 5xx for `phase: 'server'`): the call throws
     * `IndeterminateResultException`.
     *
     * `phase: 'timeout'` stubs the 408 the server answers when it gives up
     * waiting, `phase: 'redirect'` the 3xx something in front of the API
     * answers in its place, and `phase: null` the failure whose point could not
     * be proven — the classifier's default branch, reached in production by an
     * errno outside its map. All are outcomes callers have to handle, so all
     * are reachable from here.
     *
     * @param  'body'|'read'|'redirect'|'server'|'timeout'|'transfer'|null  $phase
     */
    public function stubIndeterminateResult(string $pattern, ?string $phase = 'read'): self
    {
        if ($phase === null) {
            return $this->stubTransportErrno($pattern, FakeTransportFailure::unclassifiedErrno());
        }

        // The three phases a received response produces. Building the promise
        // inside the match keeps the unreached ones unbuilt.
        $received = match ($phase) {
            'body' => static fn (): PromiseInterface => Factory::response('{invalid-json'),
            'server' => static fn (): PromiseInterface => Factory::response('Bad Gateway', 502),
            'timeout' => static fn (): PromiseInterface => Factory::response('Request Timeout', 408),
            'redirect' => static fn (): PromiseInterface => Factory::response('', 302, ['Location' => 'https://elsewhere.example/v3/payments']),
            default => null,
        };

        if ($received instanceof Closure) {
            $this->register($pattern, $received());

            return $this;
        }

        // @phpstan-ignore function.alreadyNarrowedType (PHPDoc unions are not runtime-enforced; the guard rejects invalid caller input)
        if (! in_array($phase, ['read', 'transfer'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown transport failure phase "%s"; expected one of: body, read, redirect, server, timeout, transfer, or null.',
                $phase,
            ));
        }

        return $this->stubTransportErrno($pattern, FakeTransportFailure::indeterminateErrno($phase));
    }

    /**
     * Fails the call with an arbitrary cURL errno and lets
     * {@see TransportFailureClassifier} decide what it
     * means — which is the point: the phase stubs above pin the SDK's own
     * mapping, while this one lets a test drive an errno the mapping has a line
     * for (18, 52, 55, 58, 60, 92) or none at all, and assert the classifier's
     * verdict rather than the fake's.
     */
    public function stubTransportErrno(string $pattern, int $errno): self
    {
        $this->register($pattern, static function (Request $request) use ($errno): never {
            throw FakeTransportFailure::connectException($errno, $request->toPsrRequest());
        });

        return $this;
    }

    /**
     * Keys are ignored: a filtered fixture list arrives keyed 0 and 2, and
     * {@see StubResponse::normalizePages()} reindexes before reading positions.
     *
     * @param  array<array-key, array<string, mixed>>  $pages
     */
    public function stubPages(string $pattern, array $pages): self
    {
        $sequence = $this->factory->sequence();

        foreach (StubResponse::normalizePages($pages) as $promise) {
            $sequence->pushResponse($promise);
        }

        $this->register($pattern, $sequence);

        return $this;
    }

    /**
     * @return list<array{0: Request, 1: ?Response}>
     */
    public function recorded(?string $pattern = null): array
    {
        /** @var list<array{0: Request, 1: ?Response}> $all */
        $all = $this->factory->recorded()->all();

        if ($pattern === null) {
            return $all;
        }

        $absolute = $this->resolvePattern($pattern);

        /** @var list<array{0: Request, 1: ?Response}> $filtered */
        $filtered = array_values(array_filter(
            $all,
            static fn (array $entry): bool => Str::is(Str::start($absolute, '*'), $entry[0]->url()),
        ));

        return $filtered;
    }

    /** @see StubPattern::absolute() for the matching and rejection rules. */
    protected function resolvePattern(string $pattern): string
    {
        return StubPattern::absolute($this->baseUrl, $pattern);
    }

    /**
     * Register a stub that fails the way cURL does, so Laravel marshals it
     * through `marshalConnectionException()` and records the request.
     */
    /**
     * The pattern is resolved here, not only inside the router, so an invalid
     * one is reported where it was written. The router reaches a pattern only
     * when no earlier stub matched first, so a bad pattern sitting behind a
     * catch-all was never validated at all: the caller wrote a specific stub,
     * silently got the generic one, and saw no error anywhere.
     *
     * The **resolved** pattern is the key. Keying by the raw string made
     * `'payments/*'`, `'/payments/*'` and `' payments/*'` three separate entries
     * collapsing onto one glob, where the first registered always won and the
     * rest sat in the map as dead weight — a stub that was accepted, never
     * served, and never reported, since no `NoMatchingStubException` is raised
     * while some entry does match. Registering an equivalent pattern now
     * replaces the stub it is equivalent to, keeping its position in the map so
     * the order the caller established still decides ties.
     *
     * @param  array<string, mixed>|PromiseInterface|ResponseSequence|Closure  $stub
     */
    private function register(string $pattern, array|PromiseInterface|ResponseSequence|Closure $stub): void
    {
        $this->stubs[$this->resolvePattern($pattern)] = $stub instanceof ResponseSequence
            ? $stub
            : StubResponse::normalize($stub);
    }

    /**
     * Install the single fake callback that routes every request against the
     * live stub map, captured by reference.
     *
     * The factory only ever has this one '*' entry. The closure IS the matcher:
     * it walks `$this->stubs` at call time, so post-construction stub() calls
     * are seen without rebuilding the factory or PendingRequest. Keeping the
     * factory stable means recordings accumulate naturally across the fake's
     * lifetime — register() doesn't need to wipe and re-fake anything.
     *
     * The keys are already the resolved globs — register() stores them that way
     * — so the router matches against them directly rather than re-resolving a
     * raw pattern on every request. Stub dispatch uses is_callable():
     * it covers both Closure and ResponseSequence (which exposes __invoke)
     * without producing the equivalent-mutation pairs that explicit instanceof
     * checks generate (Laravel's outer Factory wrapper invokes returned
     * Closures as a fallback, hiding the difference). If a future Guzzle
     * release ever adds __invoke to PromiseInterface this branch would need
     * tightening — verify with mutation testing before changing.
     *
     * What a callable returns is normalized by {@see StubReturnGuard}: Laravel
     * drops a falsy stub return and reads the absence as "no stub matched",
     * which sends the request to the real API, so the value cannot be handed
     * back unchecked.
     */
    private function installRouter(): void
    {
        $stubs = &$this->stubs;

        $this->factory->fake(['*' => static function (Request $request, array $options) use (&$stubs): mixed {
            foreach ($stubs as $absolute => $stub) {
                if (! Str::is(Str::start($absolute, '*'), $request->url())) {
                    continue;
                }

                return is_callable($stub)
                    ? StubReturnGuard::normalize($stub($request, $options), $absolute)
                    : $stub;
            }

            throw NoMatchingStubException::for(
                method: $request->method(),
                url: $request->url(),
                registered: array_keys($stubs),
            );
        }]);
    }

    /**
     * Build the underlying real client. The fake injects a placeholder API key
     * and intentionally omits timeouts: every request is short-circuited by the
     * router closure before reaching the network — enforced by the guarded stub
     * returns and by `preventStrayRequests()` in the constructor — so
     * timeout/connectTimeout options would only add ceremony without affecting
     * behaviour. SSL `verify` and the redirect refusal stay on to mirror
     * production wiring — the stub handler replaces the transfer handler but
     * leaves Guzzle's middleware stack in place, so without the refusal a
     * stubbed 3xx carrying a `Location` would be chased inside a test and never
     * reach the interpreter the test meant to exercise.
     */
    private function buildClient(): AsaasClient
    {
        $pendingRequest = $this->factory
            ->createPendingRequest()
            ->baseUrl($this->baseUrl)
            ->withHeader('access_token', 'fake-key')
            ->withOptions(['verify' => true, 'allow_redirects' => false]);

        return new AsaasClient(new AsaasConnector($pendingRequest, $this->baseUrl));
    }
}
