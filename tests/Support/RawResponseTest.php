<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use OwnerPro\Asaas\Support\AsaasResult;
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

it('reads a header under any casing, as the protocol defines it', function (): void {
    // Header names are case-insensitive per RFC 7230, and the SDK's own reader
    // asks for `content-disposition` while Asaas sends `Content-Disposition`.
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [
        'Content-Disposition' => 'attachment; filename="a.pdf"',
    ])));

    expect($rawResponse->header('content-disposition'))->toBe('attachment; filename="a.pdf"');
});

it('answers null for a header sent with an empty value', function (): void {
    // A field that is present but carries nothing tells a caller as much as an
    // absent one, and every caller here branches on null rather than on ''.
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, ['X-Trace' => ''])));

    expect($rawResponse->header('X-Trace'))->toBeNull();
});

it('joins a header repeated across several fields', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, ['X-Trace' => ['a', 'b']])));

    expect($rawResponse->header('X-Trace'))->toBe('a, b');
});

it('returns the raw response body', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [], '{"id":"pay_123"}')));

    expect($rawResponse->body())->toBe('{"id":"pay_123"}');
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
});

it('creates a fake response with defaults', function (): void {
    $rawResponse = RawResponse::fake();

    expect($rawResponse->status())->toBe(200);
    expect($rawResponse->body())->toBe('');
    expect($rawResponse->headers())->toBeEmpty();
});

it('hides the underlying response from debug output to prevent API key leakage', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [
        'Content-Type' => 'application/json',
    ], '{"id":"pay_123"}')));

    $debug = $rawResponse->__debugInfo();

    expect($debug)->toBe([
        'status' => 200,
        'headers' => ['Content-Type' => ['application/json']],
        'body' => '{"id":"pay_123"}',
    ]);
});

it('keeps body intact in debug info when at the size limit', function (): void {
    $body = str_repeat('a', 350);
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [], $body)));

    expect($rawResponse->__debugInfo()['body'])->toBe($body);
});

it('truncates body in debug info when over the size limit', function (): void {
    $body = 'A'.str_repeat('x', 350);
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [], $body)));

    $debug = $rawResponse->__debugInfo();

    expect($debug['body'])->toBe('A'.str_repeat('x', 349).'... <truncated; 351 chars total>');
    expect($rawResponse->body())->toBe($body);
});

it('scrubs a credential out of the body shown in debug info', function (): void {
    $body = '{"id":"acc_1","apiKey":"$aact_LIVE_SUBACCOUNT_KEY"}';
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [], $body)));

    expect($rawResponse->__debugInfo()['body'])->toBe('{"id":"acc_1","apiKey":"***"}');
});

it('still exposes the untouched body through body(), so the wire view is unchanged', function (): void {
    $body = '{"id":"acc_1","apiKey":"$aact_LIVE_SUBACCOUNT_KEY"}';
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, [], $body)));

    expect($rawResponse->body())->toBe($body);
});

it('keeps a non-JSON body in debug info, since there is nothing to scrub', function (): void {
    $rawResponse = new RawResponse(new Response(new Psr7Response(502, [], '<html>502 Bad Gateway</html>')));

    expect($rawResponse->__debugInfo()['body'])->toBe('<html>502 Bad Gateway</html>');
});

it('scrubs a credential out of the response headers too', function (): void {
    // Asaas puts none there, but this view is also shown for whatever answered
    // in its place.
    $rawResponse = new RawResponse(new Response(new Psr7Response(200, ['authToken' => 'live-webhook-secret'], '{}')));

    // The placeholder replaces the list of values, not the list itself: a
    // header field holds several, and the debug view keeps that shape.
    expect($rawResponse->__debugInfo()['headers'])->toBe(['authToken' => ['***']]);
    expect($rawResponse->headers())->toBe(['authToken' => ['live-webhook-secret']]);
});

it('collapses a repeated secret header to one placeholder, publishing no count', function (): void {
    // One placeholder rather than one per value, for the reason
    // MasksSensitiveData gives for its constant-width fill: how many values the
    // field carried is free information, so it is not handed out either.
    $psr = new Psr7Response(200, [], '{}');
    $psr = $psr->withAddedHeader('authToken', 'a')->withAddedHeader('authToken', 'b');

    expect((new RawResponse(new Response($psr)))->__debugInfo()['headers'])
        ->toBe(['authToken' => ['***']]);
});

it('carries the redacted view when a result is json-encoded, not an empty object', function (): void {
    // AsaasResult::jsonSerialize() hands this object on whole, and json_encode
    // walks public properties — of which there are none here, so the log line
    // used to read `"response":{}` where the redacted view belonged.
    $result = AsaasResult::success(
        ['id' => 'acc_1'],
        RawResponse::fake(200, ['X-Trace' => 'abc'], '{"id":"acc_1","apiKey":"$aact_live_key"}'),
    );

    $encoded = (string) json_encode($result);

    expect($encoded)
        ->toContain('"status":200')
        ->toContain('"X-Trace":["abc"]')
        ->toContain('\"apiKey\":\"***\"')
        ->not->toContain('$aact_live_key');
});
