<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\IndeterminateResultException;

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

it('throws on multipart connection error', function (): void {
    Http::fake(fn () => throw new ConnectionException('boom'));

    expect(fn () => multipartConnector()->postMultipart('/myAccount/documents/x/files', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => 'x.png',
    ]]))->toThrow(IndeterminateResultException::class);
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

it('refuses a filename the HTTP client reads as absent', function (string $filename): void {
    // Both leak the local file's name: Guzzle tests the filename with empty(),
    // so '0' is discarded and substituted exactly as '' is — even though it
    // special-cases '0' further down, where it writes the header.
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => $filename,
    ]]))->toThrow(InvalidArgumentException::class, 'reads it as absent');

    Http::assertNothingSent();
})->with(['', '0', '/tmp/0', './0']);

it('refuses an upload that names no file at all', function (): void {
    // An omitted filename never reaches the guard: attach() leaves the key out
    // and Guzzle substitutes the local name past every check here.
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'x',
    ]]))->toThrow(InvalidArgumentException::class, 'has no filename');

    Http::assertNothingSent();
});

it('still accepts a filename that merely starts with a zero', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    multipartConnector()->postMultipart('/u', [], [[
        'name' => 'documentFile', 'contents' => 'x', 'filename' => '0.png',
    ]]);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->body(), 'filename="0.png"'));
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

it('rejects a header that would break out of the part preamble before anything is sent', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);

    expect(fn (): AsaasResult => $connector->postMultipart('/myAccount/documents/doc_1', [], [[
        'name' => 'documentFile',
        'contents' => 'binary',
        'filename' => 'doc.pdf',
        'headers' => ['X-Meta' => "ok\r\nContent-Disposition: form-data; name=\"forged\""],
    ]]))->toThrow(InvalidArgumentException::class, 'control characters are not allowed');

    Http::assertNothingSent();
});

it('rejects a part header that would replace the validated content disposition', function (string $header): void {
    // Guzzle writes its own Content-Disposition only when the caller supplied
    // none, so this header would silently override the name and filename the
    // guard just validated — leaving the other half of the guard unenforceable.
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);

    expect(fn (): AsaasResult => $connector->postMultipart('/myAccount/documents/doc_1', [], [[
        'name' => 'documentFile',
        'contents' => 'binary',
        'filename' => 'doc.pdf',
        'headers' => [$header => 'form-data; name="forged"; filename="evil.exe"'],
    ]]))->toThrow(InvalidArgumentException::class, 'may not carry its own');

    Http::assertNothingSent();
})->with(['Content-Disposition', 'content-disposition', 'CONTENT-DISPOSITION']);

it('passes valid part headers through to the request', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $connector = AsaasConnector::forLaravel('key', Environment::Sandbox, 30);

    $connector->postMultipart('/myAccount/documents/doc_1', [], [[
        'name' => 'documentFile',
        'contents' => 'binary',
        'filename' => 'doc.pdf',
        'headers' => ['Content-Type' => 'application/pdf'],
    ]]);

    Http::assertSent(function ($request): bool {
        expect($request->body())->toContain('Content-Type: application/pdf');

        return true;
    });
});

it('refuses a $data entry that describes a file part, closing the guard bypass', function (): void {
    // Laravel forwards any $data entry carrying name+contents straight through
    // as a multipart element, with its own filename and headers — none of which
    // pass the guard. Writing the part in the wrong argument would otherwise
    // bypass filename, part-name and header validation entirely.
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [
        'type' => 'IDENTIFICATION',
        'evil' => [
            'name' => 'documentFile',
            'contents' => 'PAYLOAD',
            'filename' => 'a"; name="injected',
            'headers' => ['Content-Disposition' => "form-data; name=\"x\"\r\nX-Injected: yes"],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'describes a file part');

    Http::assertNothingSent();
});

it('refuses a bare file handle in $data, which would ship the local file name', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $handle = fopen('php://memory', 'r');

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', ['stray' => $handle]))
        ->toThrow(InvalidArgumentException::class, 'is a file handle');

    fclose($handle);
    Http::assertNothingSent();
});

it('refuses a $data field name that would inject part headers, closing the other guard bypass', function (): void {
    // The field name lands in the same unescaped `name="%s"` slot as a file
    // part's name, so guarding only $files left the whole upload guard
    // reachable around: this key closes the quote, appends a header of its own
    // and forges a documentFile part with a filename no guard ever saw.
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $evil = "a\"\r\nX-Injected: yes\r\nContent-Disposition: form-data; name=\"documentFile\"; filename=\"evil.exe";

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', [$evil => 'payload']))
        ->toThrow(InvalidArgumentException::class, 'Invalid multipart part name');

    Http::assertNothingSent();
});

it('refuses an empty $data field name, which names no form field at all', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    expect(fn (): AsaasResult => multipartConnector()->postMultipart('/u', ['' => 'orphan']))
        ->toThrow(InvalidArgumentException::class, 'must not be empty');

    Http::assertNothingSent();
});

it('accepts a numeric $data field name, which PHP narrows to an int key', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $result = multipartConnector()->postMultipart('/u', ['0' => 'zero']);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        expect($request->body())->toContain('name="0"');

        return true;
    });
});

it('still accepts a nested $data array that is not a part', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $result = multipartConnector()->postMultipart('/u', ['meta' => ['name' => 'no contents key']]);

    expect($result->success)->toBeTrue();
});
