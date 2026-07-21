<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\RequestNotDeliveredException;
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use OwnerPro\Asaas\Testing\FakeTransportFailure;
use PHPUnit\Framework\ExpectationFailedException;

mutates(FakeTransportFailure::class, FakeAsaasClient::class);

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

it('stubIndeterminateResult rejects unknown phases listing body among the valid ones', function (): void {
    AsaasClient::fake()->stubIndeterminateResult('transfers', 'bogus');
})->throws(InvalidArgumentException::class, 'Unknown transport failure phase "bogus"; expected one of: body, read, transfer.');

// --- fake with throwOnTransportFailure: true mirrors the typed contract ---

it('stubRequestNotDelivered throws the typed exception with the requested phase', function (): void {
    $fake = AsaasClient::fake(throwOnTransportFailure: true)
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
    $fake = AsaasClient::fake(throwOnTransportFailure: true)
        ->stubRequestNotDelivered('transfers');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (RequestNotDeliveredException $exception) {
    }

    expect($exception?->phase)->toBe('connect');
});

it('stubIndeterminateResult throws the typed exception with the requested phase', function (string $phase): void {
    $fake = AsaasClient::fake(throwOnTransportFailure: true)
        ->stubIndeterminateResult('transfers', $phase);

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe($phase);
})->with(['read', 'transfer', 'body']);

it('stubIndeterminateResult defaults to the read phase', function (): void {
    $fake = AsaasClient::fake(throwOnTransportFailure: true)
        ->stubIndeterminateResult('transfers');

    $exception = null;

    try {
        $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);
    } catch (IndeterminateResultException $exception) {
    }

    expect($exception?->phase)->toBe('read');
});

it('transport stubs are fluent and combine with regular stubs', function (): void {
    $fake = AsaasClient::fake(throwOnTransportFailure: true)
        ->stubRequestNotDelivered('transfers')
        ->stub('payments/*', ['id' => 'pay_1']);

    expect($fake)->toBeInstanceOf(FakeAsaasClient::class);
    expect($fake->payments()->find('pay_1')->success)->toBeTrue();
});

// --- fake without the flag mirrors the legacy contract ---

it('FakeAsaasClient constructor defaults throwOnTransportFailure to false', function (): void {
    $fake = (new FakeAsaasClient)->stubRequestNotDelivered('transfers');

    $result = $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
});

it('stubRequestNotDelivered returns the legacy CONNECTION_ERROR result when the flag is off', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('transfers');

    $result = $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);

    expect($result->success)->toBeFalse();
    expect($result->errors)->toBe([['code' => 'CONNECTION_ERROR', 'description' => 'Unable to connect to the Asaas API.']]);
});

// --- a failed request is still a sent request ---

it('records the request behind a stubRequestNotDelivered so assertions can see it', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    $fake->payments()->find('pay_1');

    expect($fake->recorded('payments'))->toHaveCount(1);

    $fake->assertSent('payments');
    $fake->assertSentCount(1);
});

it('records the request behind a stubIndeterminateResult so assertions can see it', function (): void {
    $fake = AsaasClient::fake()->stubIndeterminateResult('payments', 'transfer');

    $fake->payments()->find('pay_1');

    $fake->assertSent('payments');
    $fake->assertSentCount(1);
});

it('assertNotSent fails once a request was issued against a transport-failure stub', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    $fake->payments()->find('pay_1');

    $fake->assertNotSent('payments');
})->throws(ExpectationFailedException::class);

it('assertNothingSent fails once a request was issued against a transport-failure stub', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    $fake->payments()->find('pay_1');

    $fake->assertNothingSent();
})->throws(ExpectationFailedException::class);

it('records the failed request with its real url and a null response', function (): void {
    $fake = AsaasClient::fake()->stubRequestNotDelivered('payments');

    $fake->payments()->find('pay_1');

    [$request, $response] = $fake->recorded('payments')[0];

    expect($request->url())->toBe('https://api-sandbox.asaas.com/v3/payments/pay_1');
    expect($response)->toBeNull();
});

it('stubIndeterminateResult with body phase returns the legacy empty success when the flag is off', function (): void {
    $fake = AsaasClient::fake()->stubIndeterminateResult('transfers', 'body');

    $result = $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
});
