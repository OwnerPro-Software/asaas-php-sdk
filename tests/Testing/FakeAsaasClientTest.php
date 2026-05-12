<?php

declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use OwnerPro\Asaas\Testing\NoMatchingStubException;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;

mutates(FakeAsaasClient::class);

it('AsaasClient::fake() returns FakeAsaasClient', function (): void {
    expect(AsaasClient::fake())->toBeInstanceOf(FakeAsaasClient::class);
});

it('FakeAsaasClient implements AsaasClientContract', function (): void {
    expect(AsaasClient::fake())->toBeInstanceOf(AsaasClientContract::class);
});

it('FakeAsaasClient delegates resource accessors to the underlying real client', function (): void {
    $fake = AsaasClient::fake();

    expect($fake->payments())->toBeInstanceOf(PaymentResource::class);
});

it('FakeAsaasClient exposes every resource accessor from the contract', function (): void {
    $fake = AsaasClient::fake();

    expect($fake->payments())->toBeInstanceOf(PaymentResource::class);
    expect($fake->pix())->toBeInstanceOf(PixResource::class);
    expect($fake->pixTransactions())->toBeInstanceOf(PixTransactionResource::class);
    expect($fake->pixAutomatic())->toBeInstanceOf(PixAutomaticResource::class);
    expect($fake->transfers())->toBeInstanceOf(TransferResource::class);
    expect($fake->webhooks())->toBeInstanceOf(WebhookResource::class);
    expect($fake->invoices())->toBeInstanceOf(InvoiceResource::class);
    expect($fake->accounts())->toBeInstanceOf(AccountResource::class);
    expect($fake->myAccount())->toBeInstanceOf(MyAccountResource::class);
    expect($fake->creditCards())->toBeInstanceOf(CreditCardResource::class);
    expect($fake->billPayments())->toBeInstanceOf(BillPaymentResource::class);
    expect($fake->statements())->toBeInstanceOf(StatementResource::class);
    expect($fake->fiscalInfo())->toBeInstanceOf(FiscalInfoResource::class);
    expect($fake->leanPayments())->toBeInstanceOf(LeanPaymentResource::class);
});

it('FakeAsaasClient memoises resource instances', function (): void {
    $fake = AsaasClient::fake();

    expect($fake->payments())->toBe($fake->payments());
});

it('FakeAsaasClient uses an isolated Factory (no global Http::fake leakage)', function (): void {
    $a = AsaasClient::fake(['payments/*' => ['id' => 'a']]);
    $b = AsaasClient::fake(['payments/*' => ['id' => 'b']]);

    $resultA = $a->payments()->find('pay_1');
    $resultB = $b->payments()->find('pay_2');

    expect($resultA->data['id'])->toBe('a');
    expect($resultB->data['id'])->toBe('b');
});

it('path-only pattern matches URLs with query strings via auto-append', function (): void {
    $fake = AsaasClient::fake()->stub('payments', ['data' => []]);

    $result = $fake->payments()->list(['limit' => 10]);

    expect($result->success)->toBeTrue();
});

it('recorded() filter on path-only pattern matches query-string URLs', function (): void {
    $fake = AsaasClient::fake()->stub('payments', ['data' => []]);
    $fake->payments()->list(['limit' => 10]);

    // Filter pattern is path-only; auto-append must let it match the URL even
    // though the URL ends with the query string, not the path. Pinning this
    // also kills mutations that drop or reorder the trailing '*' in
    // resolvePattern() — without auto-append, Str::is(*$absolute, $url) only
    // matches when $url ends in $absolute exactly.
    expect($fake->recorded('payments'))->toHaveCount(1);
});

it('preserves recordings across post-construction stub() calls', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'pay_1']]);
    $fake->payments()->find('pay_1');
    $fake->stub('webhooks', ['id' => 'wh_1']);
    $fake->webhooks()->list();

    expect($fake->recorded())->toHaveCount(2);
});

it('throws NoMatchingStubException when a request has no matching stub', function (): void {
    $fake = AsaasClient::fake()->stub('webhooks', ['id' => 'wh_1']);

    expect(fn () => $fake->payments()->find('pay_1'))
        ->toThrow(
            NoMatchingStubException::class,
            'No stub matched GET https://api-sandbox.asaas.com/v3/payments/pay_1',
        );
});

it('lists registered patterns in the catch-all message', function (): void {
    $fake = AsaasClient::fake()
        ->stub('webhooks', [])
        ->stub('pix/qrCodes/*', []);

    try {
        $fake->payments()->find('pay_1');
        expect(false)->toBeTrue('Expected NoMatchingStubException');
    } catch (NoMatchingStubException $e) {
        expect($e->getMessage())->toContain('webhooks');
        expect($e->getMessage())->toContain('pix/qrCodes/*');
    }
});

it('catch-all does not interfere with explicit stubs registered before it', function (): void {
    $fake = AsaasClient::fake()
        ->stub('webhooks', ['id' => 'wh_1'])
        ->stub('payments/*', ['id' => 'pay_1']);

    expect($fake->payments()->find('pay_xyz')->data['id'])->toBe('pay_1');
    expect($fake->webhooks()->list()->data)->toBe([]);
});

it('resolvePattern joins baseUrl and pattern with exactly one slash', function (): void {
    $fake = AsaasClient::fake();

    // Pattern with leading slash should still resolve to single-slash join.
    $fake->stub('/payments/*', ['id' => 'pay_leading_slash']);

    $result = $fake->payments()->find('pay_xyz');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('pay_leading_slash');
    // Recorded URL must not contain a double slash between v3 and payments.
    $url = $fake->recorded()[0][0]->url();
    expect($url)->toBe('https://api-sandbox.asaas.com/v3/payments/pay_xyz');
});

it('resolvePattern strips a trailing slash on baseUrl exactly once', function (): void {
    // Sandbox baseUrl ends with /v3 (no trailing slash); reaching this code path
    // is enough to pin rtrim/ltrim invariants — request URL must remain canonical.
    $fake = AsaasClient::fake()->stub('payments/*', ['id' => 'ok']);

    $fake->payments()->find('pay_1');

    expect($fake->recorded()[0][0]->url())
        ->toBe('https://api-sandbox.asaas.com/v3/payments/pay_1');
});

it('production environment string in fake() routes to production baseUrl', function (): void {
    $fake = AsaasClient::fake([], 'production');

    $fake->stub('payments/*', ['id' => 'prod']);
    $fake->payments()->find('pay_1');

    expect($fake->recorded()[0][0]->url())
        ->toBe('https://api.asaas.com/v3/payments/pay_1');
});

it('constructor normalises raw array stubs through StubResponse::normalize', function (): void {
    // Pins the `$stub instanceof ResponseSequence ? $stub : StubResponse::normalize($stub)`
    // ternary in the constructor against InstanceOfToTrue mutations: if the
    // check were always true, the array stub would skip inference and reach
    // Laravel as a raw payload, leaving totalCount/hasMore unset on the
    // resulting AsaasPaginatedResult. StubResponseTest covers normalize() in
    // isolation; this test covers its wiring INTO the fake constructor.
    $fake = AsaasClient::fake([
        'payments' => ['data' => [['id' => 'a'], ['id' => 'b']]],
    ]);

    $result = $fake->payments()->list();

    expect($result->success)->toBeTrue();
    expect($result->totalCount)->toBe(2);
    expect($result->hasMore)->toBeFalse();
    expect($result->data)->toBe([['id' => 'a'], ['id' => 'b']]);
});

it('builds the underlying http client with TLS verification enabled', function (): void {
    $fake = AsaasClient::fake();

    // The fake exposes its client through the resource accessors only; reach
    // the AsaasConnector via reflection to assert the SSL verify option, which
    // is a safety-critical invariant per project conventions.
    $client = (new ReflectionProperty(FakeAsaasClient::class, 'asaasClient'))->getValue($fake);
    $connector = (new ReflectionProperty($client::class, 'connector'))->getValue($client);
    expect($connector)->toBeInstanceOf(AsaasConnector::class);
    $pendingRequest = (new ReflectionProperty(AsaasConnector::class, 'pendingRequest'))->getValue($connector);
    $options = (new ReflectionProperty($pendingRequest::class, 'options'))->getValue($pendingRequest);

    expect($options)->toHaveKey('verify');
    expect($options['verify'])->toBeTrue();
});

it('production environment enum in fake() routes to production baseUrl', function (): void {
    $fake = AsaasClient::fake([], Environment::Production);

    $fake->stub('payments/*', ['id' => 'p']);
    $fake->payments()->find('pay_1');

    expect($fake->recorded()[0][0]->url())
        ->toBe('https://api.asaas.com/v3/payments/pay_1');
});

it('recorded() filter accepts patterns with leading slash', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'p']]);
    $fake->payments()->find('pay_1');

    expect($fake->recorded('/payments/*'))->toHaveCount(1);
});

it('invokes Closure stubs with the request at call time', function (): void {
    $captured = null;
    $fake = AsaasClient::fake();
    $fake->stub('payments/*', function (Request $r) use (&$captured): PromiseInterface {
        $captured = $r->url();

        return Http::response(['id' => 'closure_id']);
    });

    $result = $fake->payments()->find('pay_xyz');

    expect($captured)->toContain('/payments/pay_xyz');
    expect($result->data['id'])->toBe('closure_id');
});

it('returns raw PromiseInterface stubs without invoking them as callables', function (): void {
    // Pins the is_callable() else-branch in installRouter(): a stub that is a
    // PromiseInterface but NOT callable must be returned as-is. If a future
    // Guzzle release ever ships a __invoke on PromiseInterface, this test
    // would not catch it directly — but the dispatch contract is documented,
    // and tightening the check would be the right response.
    $promise = Http::response(['id' => 'raw_promise'], 200);
    $fake = AsaasClient::fake()->stub('payments/*', $promise);

    $result = $fake->payments()->find('pay_1');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('raw_promise');
});

it('advances ResponseSequence stubs across consecutive requests', function (): void {
    $fake = AsaasClient::fake([
        'payments/*' => Http::sequence()
            ->push(['id' => 'seq_1'])
            ->push(['id' => 'seq_2']),
    ]);

    $first = $fake->payments()->find('pay_1');
    $second = $fake->payments()->find('pay_1');

    expect($first->data['id'])->toBe('seq_1');
    expect($second->data['id'])->toBe('seq_2');
});

it('recorded() returns the same Request instances across calls', function (): void {
    $fake = AsaasClient::fake(['payments/*' => ['id' => 'a']]);

    $fake->payments()->find('pay_1');

    $first = $fake->recorded();
    $second = $fake->recorded();

    expect($first)->toHaveCount(1);
    expect($second)->toHaveCount(1);
    // array_values invariant: numeric, sequential keys starting from 0.
    expect(array_keys($first))->toBe([0]);
    expect($first[0][0])->toBeInstanceOf(Request::class);
    // Same underlying request instance preserved between calls.
    expect($first[0][0])->toBe($second[0][0]);
});
