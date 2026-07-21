<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\ConnectionException;
use OwnerPro\Asaas\AsaasClient;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\RequestNotDeliveredException;
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use OwnerPro\Asaas\Testing\FakeTransportFailure;

mutates(FakeTransportFailure::class, FakeAsaasClient::class);

// --- FakeTransportFailure builds production-shaped exceptions ---

it('requestNotDelivered builds a ConnectionException wrapping a Guzzle ConnectException with the phase errno', function (string $phase, int $errno): void {
    $exception = FakeTransportFailure::requestNotDelivered($phase);

    expect($exception)->toBeInstanceOf(ConnectionException::class);

    $previous = $exception->getPrevious();
    expect($previous)->toBeInstanceOf(ConnectException::class);
    expect($previous->getHandlerContext()['errno'])->toBe($errno);
})->with([
    'dns' => ['dns', 6],
    'connect' => ['connect', 7],
    'tls' => ['tls', 35],
]);

it('indeterminateResult builds a ConnectionException wrapping a Guzzle ConnectException with the phase errno', function (string $phase, int $errno): void {
    $exception = FakeTransportFailure::indeterminateResult($phase);

    $previous = $exception->getPrevious();
    expect($previous)->toBeInstanceOf(ConnectException::class);
    expect($previous->getHandlerContext()['errno'])->toBe($errno);
})->with([
    'read' => ['read', 28],
    'transfer' => ['transfer', 56],
]);

it('requestNotDelivered rejects unknown phases', function (): void {
    FakeTransportFailure::requestNotDelivered('read');
})->throws(InvalidArgumentException::class, 'Unknown transport failure phase "read"; expected one of: dns, connect, tls.');

it('indeterminateResult rejects unknown phases', function (): void {
    FakeTransportFailure::indeterminateResult('dns');
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

it('stubIndeterminateResult with body phase returns the legacy empty success when the flag is off', function (): void {
    $fake = AsaasClient::fake()->stubIndeterminateResult('transfers', 'body');

    $result = $fake->transfers()->create(['value' => 10.0, 'pixAddressKey' => 'key@pix.com']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
});
