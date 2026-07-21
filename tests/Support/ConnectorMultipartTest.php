<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Environment;

mutates(AsaasConnector::class);

function multipartConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

it('sends multipart payload with attached files', function (): void {
    Http::fake(['*' => Http::response(['id' => 'file_1', 'status' => 'PROCESSING'], 200)]);

    $result = multipartConnector()->postMultipart(
        '/myAccount/documents/doc_1/files',
        ['type' => 'IDENTIFICATION'],
        [[
            'name' => 'documentFile',
            'contents' => 'binary-bytes',
            'filename' => 'rg.png',
        ]],
    );

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('file_1');

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/myAccount/documents/doc_1/files') {
            return false;
        }
        if (! str_contains((string) $request->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }

        $body = (string) $request->body();
        if (! str_contains($body, 'IDENTIFICATION')) {
            return false;
        }

        return true;
    });
});

it('attaches multiple files', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    multipartConnector()->postMultipart(
        '/myAccount/documents/doc_1/files',
        [],
        [
            ['name' => 'documentFile', 'contents' => 'file-1-bytes', 'filename' => 'front.png'],
            ['name' => 'documentFile', 'contents' => 'file-2-bytes', 'filename' => 'back.png'],
        ],
    );

    Http::assertSent(function ($request): bool {
        $body = (string) $request->body();

        return str_contains($body, 'front.png') && str_contains($body, 'back.png');
    });
});

it('forwards custom per-file headers to the multipart attachment', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    multipartConnector()->postMultipart(
        '/myAccount/documents/doc_1/files',
        [],
        [[
            'name' => 'documentFile',
            'contents' => 'binary-bytes',
            'filename' => 'rg.png',
            'headers' => ['X-Custom-File-Header' => 'tagged-value'],
        ]],
    );

    Http::assertSent(fn ($request): bool => str_contains((string) $request->body(), 'X-Custom-File-Header')
        && str_contains((string) $request->body(), 'tagged-value'));
});

it('stringifies boolean fields in the multipart payload', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    multipartConnector()->postMultipart('/fiscalInfo/', [
        'simplesNacional' => false,
        'culturalProjectsPromoter' => true,
    ]);

    Http::assertSent(function ($request): bool {
        $body = (string) $request->body();

        return str_contains($body, "name=\"simplesNacional\"\r\nContent-Length: 5\r\n\r\nfalse")
            && str_contains($body, "name=\"culturalProjectsPromoter\"\r\nContent-Length: 4\r\n\r\ntrue");
    });
});

it('stringifies boolean values inside nested arrays before Laravel flattens them', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    multipartConnector()->postMultipart('/checkout/', [
        'flags' => [false, true],
    ]);

    // Laravel 12 flattens nested arrays to `flags[]`; Laravel 13.20+ to
    // `flags[0]`/`flags[1]`. PHP-style servers parse both identically, so the
    // index style is framework detail — the invariant pinned here is only
    // that booleans arrive as the literal strings "false"/"true".
    Http::assertSent(function ($request): bool {
        $body = (string) $request->body();

        return preg_match('/name="flags\[\d*\]"\r\nContent-Length: 5\r\n\r\nfalse/', $body) === 1
            && preg_match('/name="flags\[\d*\]"\r\nContent-Length: 4\r\n\r\ntrue/', $body) === 1;
    });
});

it('sends form-only multipart when no files are attached', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $result = multipartConnector()->postMultipart('/fiscalInfo/', ['email' => 'a@b.c', 'simplesNacional' => true]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        if (! str_contains((string) $request->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }
        $body = (string) $request->body();

        return str_contains($body, 'name="email"') && str_contains($body, 'a@b.c');
    });
});

it('returns failure on multipart connection error', function (): void {
    Http::fake(fn () => throw new ConnectionException('boom'));

    $result = multipartConnector()->postMultipart('/myAccount/documents/x/files', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    expect($result->success)->toBeFalse();
    expect($result->errors[0]['code'])->toBe('CONNECTION_ERROR');
});

it('returns failure when API rejects the upload', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'invalid file']]], 400)]);

    $result = multipartConnector()->postMultipart('/myAccount/documents/x/files', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    expect($result->success)->toBeFalse();
    expect($result->errors[0]['description'])->toBe('invalid file');
    expect($result->response->status())->toBe(400);
});

it('restores JSON body format after a multipart upload', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    $connector->postMultipart('/upload', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    $connector->post('/payments', ['value' => 100]);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/payments')
        && str_contains((string) $request->header('Content-Type')[0], 'application/json'));
});

it('does not leak attached files between sequential multipart calls', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'FIRST', 'filename' => 'first.png',
    ]]);
    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'SECOND', 'filename' => 'second.png',
    ]]);

    $requests = Http::recorded();
    $secondBody = (string) $requests[1][0]->body();

    expect($secondBody)->toContain('second.png');
    expect($secondBody)->not->toContain('first.png');
});

it('sends a multipart Content-Type on every sequential upload', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'FIRST', 'filename' => 'first.png',
    ]]);
    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'SECOND', 'filename' => 'second.png',
    ]]);

    $requests = Http::recorded();

    expect((string) $requests[0][0]->header('Content-Type')[0])->toStartWith('multipart/form-data; boundary=');
    expect((string) $requests[1][0]->header('Content-Type')[0])->toStartWith('multipart/form-data; boundary=');
});

it('refuses a filename that would inject part headers into the multipart body', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $upload = fn (): AsaasResult => multipartConnector()->postMultipart('/u', [], [[
        'name' => 'documentFile',
        'contents' => 'x',
        'filename' => "ok.png\"\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\nIDENTIFICATION",
    ]]);

    expect($upload)->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('does not attach earlier files when a later filename is rejected', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    expect(fn (): AsaasResult => $connector->postMultipart('/u', [], [
        ['name' => 'documentFile', 'contents' => 'FIRST', 'filename' => 'first.png'],
        ['name' => 'documentFile', 'contents' => 'EVIL', 'filename' => "evil.png\r\n"],
    ]))->toThrow(InvalidArgumentException::class);

    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'SECOND', 'filename' => 'second.png',
    ]]);

    $body = (string) Http::recorded()[0][0]->body();

    expect($body)->toContain('second.png')
        ->and($body)->not->toContain('first.png')
        ->and($body)->not->toContain('FIRST');
});

it('refuses an empty filename that would leak the local file path', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => '',
    ]]))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('refuses a filename whose trailing backslash escapes the closing quote', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'ok.png\\',
    ]]))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('refuses a part name that would inject part headers into the multipart body', function (string $partName): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [], [[
        'name' => $partName, 'contents' => 'x', 'filename' => 'rg.png',
    ]]))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
})->with([
    'quote' => 'documentFile"; filename="evil.exe',
    'trailing backslash' => 'documentFile\\',
    'crlf' => "documentFile\r\nX-Injected: 1",
    'empty' => '',
]);

it('does not attach earlier files when a later part name is rejected', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = multipartConnector();

    expect(fn (): AsaasResult => $connector->postMultipart('/u', [], [
        ['name' => 'documentFile', 'contents' => 'FIRST', 'filename' => 'first.png'],
        ['name' => "documentFile\r\n", 'contents' => 'EVIL', 'filename' => 'evil.png'],
    ]))->toThrow(InvalidArgumentException::class);

    $connector->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'SECOND', 'filename' => 'second.png',
    ]]);

    $body = (string) Http::recorded()[0][0]->body();

    expect($body)->toContain('second.png')
        ->and($body)->not->toContain('first.png')
        ->and($body)->not->toContain('FIRST');
});

it('restores JSON body format even when the multipart upload fails', function (): void {
    Http::fakeSequence()
        ->push(['errors' => [['description' => 'rejected']]], 400)
        ->push(['ok' => true], 200);

    $connector = multipartConnector();

    $connector->postMultipart('/upload', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]);

    $connector->post('/payments', ['value' => 100]);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/payments')
        && str_contains((string) $request->header('Content-Type')[0], 'application/json'));
});
