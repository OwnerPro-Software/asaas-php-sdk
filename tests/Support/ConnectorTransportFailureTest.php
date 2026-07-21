<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\RequestNotDeliveredException;

mutates(AsaasConnector::class);

/** @param array<string, mixed> $context */
function throwingTransportStub(array $context): Closure
{
    $curlFailure = new ConnectException(
        'cURL failure',
        new Psr7Request('POST', 'https://api-sandbox.asaas.com/v3/transfers'),
        null,
        $context,
    );

    $connectionException = new ConnectionException($curlFailure->getMessage(), 0, $curlFailure);

    return static fn (): never => throw $connectionException;
}

function throwingConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('key', Environment::Sandbox, 30, throwOnTransportFailure: true);
}

// --- throwOnTransportFailure: true ---

it('throws RequestNotDeliveredException when the connection provably failed', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 7])]);

    $exception = null;

    try {
        throwingConnector()->get('/payments/pay_123');
    } catch (RequestNotDeliveredException $exception) {
    }

    expect($exception)->toBeInstanceOf(RequestNotDeliveredException::class);
    expect($exception->phase)->toBe('connect');
    expect($exception->getPrevious())->toBeInstanceOf(ConnectionException::class);
});

it('throws IndeterminateResultException on read timeout after an established connection', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 28, 'connect_time' => 0.042])]);

    $exception = null;

    try {
        throwingConnector()->post('/transfers', ['value' => 10.0]);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('read');
    expect($exception->response)->toBeNull();
});

it('throws IndeterminateResultException with body phase carrying the uninterpretable response on 2xx with invalid JSON', function (): void {
    Http::fake(['*' => Http::response('{invalid-json', 200)]);

    $exception = null;

    try {
        throwingConnector()->get('/payments/pay_123');
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->getMessage())->toContain('reconcile before retrying');
    expect($exception->phase)->toBe('body');
    expect($exception->response?->status())->toBe(200);
    expect($exception->response?->body())->toBe('{invalid-json');
});

it('throws IndeterminateResultException with body phase on 2xx with empty body', function (): void {
    Http::fake(['*' => Http::response('', 200)]);

    $exception = null;

    try {
        throwingConnector()->get('/payments/pay_123');
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('body');
});

it('treats 204 No Content as definitive success when throwing is enabled', function (): void {
    Http::fake(['*' => Http::response('', 204)]);

    $result = throwingConnector()->delete('/accounts/acc_1/accessTokens');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
});

/*
 * Contract guard for the illuminate/http version in use: PendingRequest must
 * wrap a response-less Guzzle RequestException (mid-transfer failures like
 * cURL 55/56) into Illuminate's ConnectionException. If a framework version
 * stops doing that, the exception would leak raw in BOTH modes and the
 * classifier's RequestException branch would become dead code — this test
 * fails loudly instead. Runs against every version in the CI matrix.
 */
it('relies on Laravel wrapping response-less Guzzle RequestException into ConnectionException', function (): void {
    Http::fake(['*' => function (): never {
        throw new RequestException(
            'cURL error 56',
            new Psr7Request('POST', 'https://api-sandbox.asaas.com/v3/transfers'),
            null,
            null,
            ['errno' => 56],
        );
    }]);

    $exception = null;

    try {
        throwingConnector()->post('/transfers', ['value' => 10.0]);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('transfer');
    expect($exception->getPrevious())->toBeInstanceOf(ConnectionException::class);
});

it('still returns a failure result on definitive HTTP errors when throwing is enabled', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['code' => 'invalid_value', 'description' => 'bad']]], 400)]);

    $result = throwingConnector()->get('/payments/pay_123');

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'invalid_value', 'description' => 'bad']]);
});

it('still returns a success result on healthy 2xx JSON when throwing is enabled', function (): void {
    Http::fake(['*' => Http::response(['id' => 'pay_123'], 200)]);

    $result = throwingConnector()->get('/payments/pay_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe(['id' => 'pay_123']);
});

it('propagates typed transport exceptions through delete and put', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 6])]);

    $connector = throwingConnector();

    expect(fn () => $connector->delete('/payments/pay_123'))->toThrow(RequestNotDeliveredException::class);
    expect(fn () => $connector->put('/payments/pay_123', []))->toThrow(RequestNotDeliveredException::class);
});

it('propagates typed transport exceptions through postMultipart', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 6])]);

    $connector = throwingConnector();

    expect(fn () => $connector->postMultipart('/accounts/acc_1/documents/doc_1', ['type' => 'INVOICE'], [
        ['name' => 'documentFile', 'contents' => 'pdf-bytes', 'filename' => 'doc.pdf'],
    ]))->toThrow(RequestNotDeliveredException::class);
});

it('propagates typed transport exceptions through paginate', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 28])]);

    expect(fn () => throwingConnector()->paginate('/payments', []))
        ->toThrow(IndeterminateResultException::class);
});

it('propagates typed transport exceptions through all() iteration', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 28])]);

    expect(function (): void {
        foreach (throwingConnector()->all('/payments', []) as $row) {
        }
    })->toThrow(IndeterminateResultException::class);
});

// --- throwOnTransportFailure: false (legacy default) ---

// Pins the CHANGELOG promise: "Direct 2.0-style construction keeps working
// unchanged" — two-argument construction must keep the legacy transport
// behavior. (This does NOT kill the constructor-default mutant — Xdebug never
// attributes default-argument evaluation to the parameter line, verified
// empirically — hence the @pest-mutate-ignore on it; this test pins BC only.)
it('2.0-style two-argument construction keeps the legacy CONNECTION_ERROR behavior', function (): void {
    $pendingRequest = (new PendingRequest)
        ->baseUrl('https://api-sandbox.asaas.com/v3')
        ->withHeader('access_token', 'test-key')
        ->timeout(30)
        ->preventStrayRequests()
        ->stub([fn (): never => throw new ConnectionException('cURL error 28: Connection timed out')]);

    $connector = new AsaasConnector($pendingRequest, '');
    $result = $connector->get('/payments/pay_123');

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
});

// Reflection justified (CLAUDE.md exception): forStandalone builds a raw
// PendingRequest with no factory, so Http::fake cannot intercept it — the
// default is unobservable through behavior without real network I/O, and a
// wrong default here is the money-safety invariant of this feature.
it('forStandalone defaults throwOnTransportFailure to false', function (): void {
    $connector = AsaasConnector::forStandalone('key', Environment::Sandbox, 30);

    $flag = (new ReflectionProperty(AsaasConnector::class, 'throwOnTransportFailure'))->getValue($connector);

    expect($flag)->toBeFalse();
});

it('keeps the legacy CONNECTION_ERROR result by default', function (): void {
    Http::fake(['*' => throwingTransportStub(['errno' => 7])]);

    $result = AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->get('/payments/pay_123');

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
});

it('keeps the legacy empty-data success on unreadable 2xx body by default', function (): void {
    Http::fake(['*' => Http::response('{invalid-json', 200)]);

    $result = AsaasConnector::forLaravel('key', Environment::Sandbox, 30)->get('/payments/pay_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
});
