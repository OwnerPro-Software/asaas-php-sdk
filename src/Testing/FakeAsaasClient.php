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
        private readonly bool $throwOnTransportFailure = false,
    ) {
        $this->baseUrl = ($environment instanceof Environment ? $environment : Environment::from($environment))->baseUrl();
        $this->factory = new Factory;

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
     * connect, TLS). With `throwOnTransportFailure: true` the call throws
     * `RequestNotDeliveredException`; without it, the legacy
     * `CONNECTION_ERROR` result is returned — mirroring production.
     *
     * @param  'connect'|'dns'|'tls'  $phase
     */
    public function stubRequestNotDelivered(string $pattern, string $phase = 'connect'): self
    {
        return $this->stubTransportFailure($pattern, FakeTransportFailure::notDeliveredErrno($phase));
    }

    /**
     * Simulates a failure after the request may have been processed (read
     * timeout, connection dropped mid-transfer, or a 2xx with unreadable
     * body for `phase: 'body'`). With `throwOnTransportFailure: true` the
     * call throws `IndeterminateResultException`; without it, the legacy
     * behaviour is returned — mirroring production.
     *
     * @param  'body'|'read'|'transfer'  $phase
     */
    public function stubIndeterminateResult(string $pattern, string $phase = 'read'): self
    {
        if ($phase === 'body') {
            $this->register($pattern, Factory::response('{invalid-json'));

            return $this;
        }

        // @phpstan-ignore function.alreadyNarrowedType (PHPDoc unions are not runtime-enforced; the guard rejects invalid caller input)
        if (! in_array($phase, ['read', 'transfer'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown transport failure phase "%s"; expected one of: body, read, transfer.',
                $phase,
            ));
        }

        return $this->stubTransportFailure($pattern, FakeTransportFailure::indeterminateErrno($phase));
    }

    /** @param list<array<string, mixed>> $pages */
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
    private function stubTransportFailure(string $pattern, int $errno): self
    {
        $this->register($pattern, static function (Request $request) use ($errno): never {
            throw FakeTransportFailure::connectException($errno, $request->toPsrRequest());
        });

        return $this;
    }

    /**
     * The pattern is resolved here, not only inside the router, so an invalid
     * one is reported where it was written. The router reaches a pattern only
     * when no earlier stub matched first, so a bad pattern sitting behind a
     * catch-all was never validated at all: the caller wrote a specific stub,
     * silently got the generic one, and saw no error anywhere.
     *
     * @param  array<string, mixed>|PromiseInterface|ResponseSequence|Closure  $stub
     */
    private function register(string $pattern, array|PromiseInterface|ResponseSequence|Closure $stub): void
    {
        $this->resolvePattern($pattern);

        $this->stubs[$pattern] = $stub instanceof ResponseSequence ? $stub : StubResponse::normalize($stub);
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
     * Pattern resolution is delegated to StubPattern::absolute() — single source
     * of truth shared with resolvePattern(). Stub dispatch uses is_callable():
     * it covers both Closure and ResponseSequence (which exposes __invoke)
     * without producing the equivalent-mutation pairs that explicit instanceof
     * checks generate (Laravel's outer Factory wrapper invokes returned
     * Closures as a fallback, hiding the difference). If a future Guzzle
     * release ever adds __invoke to PromiseInterface this branch would need
     * tightening — verify with mutation testing before changing.
     */
    private function installRouter(): void
    {
        $stubs = &$this->stubs;
        $baseUrl = $this->baseUrl;

        $this->factory->fake(['*' => static function (Request $request, array $options) use (&$stubs, $baseUrl): mixed {
            foreach ($stubs as $pattern => $stub) {
                $absolute = StubPattern::absolute($baseUrl, $pattern);

                if (! Str::is(Str::start($absolute, '*'), $request->url())) {
                    continue;
                }

                return is_callable($stub) ? $stub($request, $options) : $stub;
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
     * router closure before reaching the network, so timeout/connectTimeout
     * options would only add ceremony without affecting behaviour. SSL `verify`
     * stays on to mirror production wiring.
     */
    private function buildClient(): AsaasClient
    {
        $pendingRequest = $this->factory
            ->createPendingRequest()
            ->baseUrl($this->baseUrl)
            ->withHeader('access_token', 'fake-key')
            ->withOptions(['verify' => true]);

        return new AsaasClient(new AsaasConnector($pendingRequest, $this->baseUrl, $this->throwOnTransportFailure));
    }
}
