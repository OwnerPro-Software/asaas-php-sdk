<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Client\ConnectionException;
use OwnerPro\Asaas\Support\AsaasException;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\IndeterminateResultException;
use OwnerPro\Asaas\Support\RequestNotDeliveredException;
use OwnerPro\Asaas\Support\TransportException;
use OwnerPro\Asaas\Support\TransportFailureClassifier;

mutates(
    TransportFailureClassifier::class,
    RequestNotDeliveredException::class,
    IndeterminateResultException::class,
);

/** @param array<string, mixed> $context */
function connectionExceptionWithCurlContext(array $context): ConnectionException
{
    $curlFailure = new ConnectException(
        'cURL failure',
        new Psr7Request('POST', 'https://api-sandbox.asaas.com/v3/transfers'),
        null,
        $context,
    );

    return new ConnectionException($curlFailure->getMessage(), 0, $curlFailure);
}

// --- request provably not delivered ---

it('classifies DNS failure (cURL 6) as RequestNotDelivered with dns phase', function (): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 6]));

    expect($exception)->toBeInstanceOf(RequestNotDeliveredException::class);
    expect($exception->phase)->toBe('dns');
});

it('classifies connection refused (cURL 7) as RequestNotDelivered with connect phase', function (): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 7]));

    expect($exception)->toBeInstanceOf(RequestNotDeliveredException::class);
    expect($exception->phase)->toBe('connect');
});

it('classifies TLS failures as RequestNotDelivered with tls phase', function (int $errno): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => $errno]));

    expect($exception)->toBeInstanceOf(RequestNotDeliveredException::class);
    expect($exception->phase)->toBe('tls');
})->with([
    'ssl connect error (35)' => 35,
    'client cert problem (58)' => 58,
    'peer verification failed (60)' => 60,
]);

// --- indeterminate: the request may have been processed ---

it('classifies timeout (cURL 28) with connect_time 0.0 as indeterminate — reused keep-alive connections report zeroed connection timers, so 0.0 proves nothing', function (): void {
    $exception = TransportFailureClassifier::classify(
        connectionExceptionWithCurlContext(['errno' => 28, 'connect_time' => 0.0]),
    );

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('read');
});

it('classifies timeout (cURL 28) as indeterminate read regardless of connect_time value', function (): void {
    $exception = TransportFailureClassifier::classify(
        connectionExceptionWithCurlContext(['errno' => 28, 'connect_time' => 0.042]),
    );

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('read');
});

it('classifies timeout (cURL 28) without connect_time evidence as indeterminate read', function (): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 28]));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('read');
});

it('classifies empty reply (cURL 52) as indeterminate read', function (): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 52]));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('read');
});

it('classifies mid-transfer failures as indeterminate transfer', function (int $errno): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => $errno]));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('transfer');
})->with([
    'partial file (18)' => 18,
    'send error (55)' => 55,
    'recv error (56)' => 56,
    'http2 stream error (92)' => 92,
]);

it('classifies mid-transfer failure wrapped in a response-less RequestException as indeterminate transfer', function (): void {
    $curlFailure = new RequestException(
        'cURL error 56',
        new Psr7Request('POST', 'https://api-sandbox.asaas.com/v3/transfers'),
        null,
        null,
        ['errno' => 56],
    );

    $exception = TransportFailureClassifier::classify(
        new ConnectionException($curlFailure->getMessage(), 0, $curlFailure),
    );

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBe('transfer');
});

// --- ambiguity bias: anything unprovable is indeterminate ---

it('classifies unknown cURL errno as indeterminate with null phase', function (): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 99]));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBeNull();
});

it('classifies missing previous exception as indeterminate with null phase', function (): void {
    $exception = TransportFailureClassifier::classify(new ConnectionException('boom'));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBeNull();
});

it('classifies non-Guzzle previous exception as indeterminate with null phase', function (): void {
    $exception = TransportFailureClassifier::classify(
        new ConnectionException('boom', 0, new RuntimeException('not guzzle')),
    );

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBeNull();
});

it('classifies non-integer errno in handler context as indeterminate with null phase', function (): void {
    $exception = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => '28']));

    expect($exception)->toBeInstanceOf(IndeterminateResultException::class);
    expect($exception->phase)->toBeNull();
});

// --- contract surface ---

it('preserves the original ConnectionException in getPrevious()', function (): void {
    $connectionException = connectionExceptionWithCurlContext(['errno' => 7]);

    $exception = TransportFailureClassifier::classify($connectionException);

    expect($exception->getPrevious())->toBe($connectionException);
});

it('produces exceptions rooted in the TransportException/AsaasException hierarchy', function (): void {
    $notDelivered = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 7]));
    $indeterminate = TransportFailureClassifier::classify(connectionExceptionWithCurlContext(['errno' => 28]));

    expect($notDelivered)->toBeInstanceOf(TransportException::class);
    expect($notDelivered)->toBeInstanceOf(AsaasException::class);
    expect($indeterminate)->toBeInstanceOf(TransportException::class);
    expect($indeterminate)->toBeInstanceOf(AsaasException::class);
});

it('RequestNotDeliveredException message names the phase and states retry safety', function (): void {
    $exception = new RequestNotDeliveredException('dns');

    expect($exception->getMessage())->toBe('The request never reached the Asaas API (dns failure); it is safe to retry.');
    expect($exception->getCode())->toBe(0);
});

it('IndeterminateResultException message demands reconciliation and defaults phase to null', function (): void {
    $exception = new IndeterminateResultException;

    expect($exception->getMessage())->toBe('The Asaas API may or may not have processed the request; reconcile before retrying.');
    expect($exception->phase)->toBeNull();
    expect($exception->getCode())->toBe(0);
});

it('AsaasRequestException converges under the AsaasException base', function (): void {
    $exception = new AsaasRequestException([], null);

    expect($exception)->toBeInstanceOf(AsaasException::class);
});
