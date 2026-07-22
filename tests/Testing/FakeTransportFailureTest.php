<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Client\Response as ClientResponse;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\RequestNotDeliveredException;
use OwnerPro\Asaas\Support\TransportException;
use OwnerPro\Asaas\Support\TransportFailureClassifier;
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use OwnerPro\Asaas\Testing\FakeTransportFailure;
use PHPUnit\Framework\ExpectationFailedException;

mutates(FakeTransportFailure::class, FakeAsaasClient::class);

/**
 * A transport-failure stub always throws now, but these tests are about what
 * the failed call left behind on the recorder, not about the exception.
 */
function sendPastTransportFailure(FakeAsaasClient $fake): void
{
    try {
        $fake->payments()->find('pay_1');
    } catch (TransportException) {
    }
}

// --- FakeTransportFailure builds production-shaped exceptions ---

it('notDeliveredErrno maps each phase to its cURL errno', function (string $phase, int $errno): void {
    expect(FakeTransportFailure::notDeliveredErrno($phase))->toBe($errno);
})->with([
    'dns' => ['dns', 6],
    'connect' => ['connect', 7],
    'tls' => ['tls', 35],
]);

it('indeterminateErrno maps each phase to its cURL errno', function (string $phase, int $errno): void {
    expect(FakeTransportFailure::indeterminateErrno($phase))->toBe($errno);
})->with([
    'read' => ['read', 28],
    'transfer' => ['transfer', 56],
]);

it('connectException carries the errno in the handler context and the given request', function (): void {
    $request = new Request('POST', 'https://asaas.test/v3/transfers');

    $exception = FakeTransportFailure::connectException(7, $request);

    expect($exception)->toBeInstanceOf(ConnectException::class);
    expect($exception->getHandlerContext()['errno'])->toBe(7);
    expect($exception->getRequest())->toBe($request);
    expect($exception->getMessage())->toBe('Simulated transport failure (cURL error 7)');
});

it('notDeliveredErrno rejects unknown phases', function (): void {
    FakeTransportFailure::notDeliveredErrno('read');
})->throws(InvalidArgumentException::class, 'Unknown transport failure phase "read"; expected one of: dns, connect, tls.');

it('indeterminateErrno rejects unknown phases', function (): void {
    FakeTransportFailure::indeterminateErrno('dns');
})->throws(InvalidArgumentException::class, 'Unknown transport failure phase "dns"; expected one of: read, transfer.');

it('stubIndeterminateResult rejects unknown phases listing body and server among the valid ones', function (): void {
    AsaasClient::fake()->stubIndeterminateResult('transfers', 'bogus');
})->throws(InvalidArgumentException::class, 'Unknown transport failure phase "bogus"; expected one of: body, read, redirect, server, timeout, transfer, or null.');

// --- the fake mirrors the typed contract ---

it('stubRequestNotDelivered throws the typed exception with the requested phase', function (): void {
    $fake = AsaasClient::fake()
        ->stubRequestNotDelivered('transfers', 'dns');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (RequestNotDeliveredException $exception) {
    }

    expect($exception)->toBeInstanceOf(RequestNotDeliveredException::class);
    expect($exception->phase)->toBe('dns');
});

it('stubRequestNotDelivered defaults to the connect phase', function (): void {
    $fake = AsaasClient::fake()
        ->stubRequestNotDelivered('transfers');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (RequestNotDeliveredException $exception) {
    }

    expect($exception?->phase)->toBe('connect');
});

it('stubIndeterminateResult throws the typed exception with the requested phase', function (string $phase): void {
    $fake = AsaasClient::fake()
        ->stubIndeterminateResult('transfers', $phase);

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe($phase);
})->with(['read', 'transfer', 'body', 'server', 'timeout', 'redirect']);

// The classifier's default branch is what production reaches on an errno
// outside its map, so a test has to be able to reach it too.
it('stubIndeterminateResult with a null phase reaches the unproven-failure branch', function (): void {
    $fake = AsaasClient::fake()
        ->stubIndeterminateResult('transfers', phase: null);

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBeNull();
});

it('unclassifiedErrno stays outside the classifier map', function (): void {
    // The value is pinned, not just its effect: a test asserting only "the
    // classifier ignores it" passes for every unmapped number, so it would not
    // notice the day this stops being CURLE_HTTP3.
    expect(FakeTransportFailure::unclassifiedErrno())->toBe(95);

    $connectionException = new ConnectionException(
        'boom',
        0,
        FakeTransportFailure::connectException(FakeTransportFailure::unclassifiedErrno(), new Request('POST', 'https://asaas.test/v3/transfers')),
    );

    $classified = TransportFailureClassifier::classify($connectionException);

    expect($classified)->toBeInstanceOf(IndeterminateResultException::class);
    expect($classified->phase)->toBeNull();
});

// The point of the errno stub: drive an errno the classifier has a line for
// and assert the classifier's verdict, not the fake's phase table.
it('stubTransportErrno lets the classifier decide, covering every mapped errno', function (int $errno, string $exceptionClass, ?string $phase): void {
    $fake = AsaasClient::fake()->stubTransportErrno('transfers', $errno);

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (TransportException $exception) {
    }

    expect($exception)->toBeInstanceOf($exceptionClass);
    expect($exception->phase)->toBe($phase);
})->with([
    'dns' => [6, RequestNotDeliveredException::class, 'dns'],
    'connect' => [7, RequestNotDeliveredException::class, 'connect'],
    'ssl connect' => [35, RequestNotDeliveredException::class, 'tls'],
    'ssl cert problem' => [58, RequestNotDeliveredException::class, 'tls'],
    'peer verification' => [60, RequestNotDeliveredException::class, 'tls'],
    'timeout' => [28, IndeterminateResultException::class, 'read'],
    'got nothing' => [52, IndeterminateResultException::class, 'read'],
    'partial file' => [18, IndeterminateResultException::class, 'transfer'],
    'send error' => [55, IndeterminateResultException::class, 'transfer'],
    'recv error' => [56, IndeterminateResultException::class, 'transfer'],
    'http2 stream' => [92, IndeterminateResultException::class, 'transfer'],
    'unmapped' => [999, IndeterminateResultException::class, null],
]);

it('stubIndeterminateResult defaults to the read phase', function (): void {
    $fake = AsaasClient::fake()
        ->stubIndeterminateResult('transfers');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception?->phase)->toBe('read');
});

it('transport stubs are fluent and combine with regular stubs', function (): void {
    $fake = AsaasClient::fake()
        ->stubRequestNotDelivered('transfers')
        ->stub('payments/*', ['id' => 'pay_1']);

    expect($fake)->toBeInstanceOf(FakeAsaasClient::class);
    expect($fake->payments()->find('pay_1')->success)->toBeTrue();
});

it('a directly constructed FakeAsaasClient throws the typed exception too', function (): void {
    $fake = (new FakeAsaasClient)->stubRequestNotDelivered('transfers');

    expect(fn () => $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']))
        ->toThrow(RequestNotDeliveredException::class);
});

/*
 * --- a failed request is still a sent request ---
 *
 * Contract guard for the illuminate/http version in use: the transport stubs
 * make the request visible to the assertion helpers by throwing a Guzzle
 * ConnectException, relying on PendingRequest::marshalConnectionException()
 * to call Factory::recordRequestResponsePair($request, null) before rethrowing
 * as an Illuminate ConnectionException. If a framework version stops recording
 * there, these tests fail loudly instead of silently restoring the vacuous
 * assertNotSent() they were written to kill. Runs against every version in the
 * CI matrix — check them first on a Laravel major bump.
 */

it('records the request behind a stubRequestNotDelivered so assertions can see it', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    sendPastTransportFailure($fake);

    expect($fake->recorded('payments'))->toHaveCount(1);

    $fake->assertSent('payments');
    $fake->assertSentCount(1);
});

it('records the request behind a stubIndeterminateResult so assertions can see it', function (): void {
    $fake = AsaasClient::fake()->stubIndeterminateResult('payments', 'transfer');

    sendPastTransportFailure($fake);

    $fake->assertSent('payments');
    $fake->assertSentCount(1);
});

it('assertNotSent fails once a request was issued against a transport-failure stub', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    sendPastTransportFailure($fake);

    $fake->assertNotSent('payments');
})->throws(ExpectationFailedException::class);

it('assertNothingSent fails once a request was issued against a transport-failure stub', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    sendPastTransportFailure($fake);

    $fake->assertNothingSent();
})->throws(ExpectationFailedException::class);

it('records the failed request with its real url and a null response', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    sendPastTransportFailure($fake);

    [$request, $response] = $fake->recorded('payments')[0];

    expect($request->url())->toBe('https://api-sandbox.asaas.com/v3/payments/pay_1');
    expect($response)->toBeNull();
});

it('hands the null response to a matcher closure that accepts it', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    sendPastTransportFailure($fake);

    $fake->assertSent(
        'payments',
        fn (ClientRequest $request, ?ClientResponse $response): bool => $response === null,
    );
});

it('stubIndeterminateResult with server phase carries the 5xx response on the thrown exception', function (): void {
    $fake = AsaasClient::fake()
        ->stubIndeterminateResult('transfers', 'server');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception?->response?->status())->toBe(502);
    expect($exception?->response?->body())->toBe('Bad Gateway');
});

// The phase stubs exist to serve what production actually sees, so the shape
// each one produces is part of the contract — not just the phase it lands on.
// Every 3xx classifies as 'redirect', so asserting the phase alone would leave
// the status, the Location header and the empty body unpinned.
it('stubIndeterminateResult redirect phase serves the 302 production would see', function (): void {
    $fake = AsaasClient::fake()->stubIndeterminateResult('transfers', 'redirect');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception?->response?->status())->toBe(302)
        // A 3xx without a Location is not what Guzzle would have chased, so the
        // stub carries one: it models the redirect that is refused, not an
        // oddity no middleware would have acted on.
        ->and($exception?->response?->header('Location'))->not->toBeNull()
        ->and($exception?->response?->body())->toBe('');
});
