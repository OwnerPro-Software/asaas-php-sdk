<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use OwnerPro\Asaas\Support\RawResponse;

mutates(RawResponse::class);

it('returns the HTTP status code', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(201)));

    expect($rawResponse->status())->toBe(201);
});

it('returns all headers', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [
        'X-Request-Id' => 'req_abc',
        'X-Rate-Limit' => '100',
    ])));

    $headers = $rawResponse->headers();

    expect($headers['X-Request-Id'])->toBe(['req_abc']);
    expect($headers['X-Rate-Limit'])->toBe(['100']);
});

it('returns a single header value', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [
        'X-Request-Id' => 'req_abc',
    ])));

    expect($rawResponse->header('X-Request-Id'))->toBe('req_abc');
});

it('returns null for missing header', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200)));

    expect($rawResponse->header('X-Missing'))->toBeNull();
});

it('returns the raw response body', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [], '{"id":"pay_123"}')));

    expect($rawResponse->body())->toBe('{"id":"pay_123"}');
});

it('returns the underlying Laravel response', function (): void {
    $laravelResponse = new Response(new Psr7Response(200));
    $rawResponse = new RawResponse($laravelResponse);

    expect($rawResponse->toUnderlying())->toBe($laravelResponse);
});

it('creates a fake response from primitives', function (): void {
    $rawResponse = RawResponse::fake(
        status: 429,
        headers: ['X-Rate-Limit-Remaining' => '0'],
        body: '{"error":"rate limited"}',
    );

    expect($rawResponse->status())->toBe(429);
    expect($rawResponse->header('X-Rate-Limit-Remaining'))->toBe('0');
    expect($rawResponse->body())->toBe('{"error":"rate limited"}');
    expect($rawResponse->toUnderlying())->toBeInstanceOf(Response::class);
});

it('creates a fake response with defaults', function (): void {
    $rawResponse = RawResponse::fake();

    expect($rawResponse->status())->toBe(200);
    expect($rawResponse->body())->toBe('');
    expect($rawResponse->headers())->toBeEmpty();
});
