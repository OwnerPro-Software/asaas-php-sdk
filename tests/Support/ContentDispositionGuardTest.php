<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\ContentDispositionGuard;

mutates(ContentDispositionGuard::class);

it('accepts ordinary upload filenames', function (string $filename): void {
    expect(ContentDispositionGuard::filename($filename))->toBe($filename);
})->with([
    'rg.png',
    'contrato social.pdf',
    'certidão-negativa.pdf',
    'logo.png',
    str_repeat('a', 251).'.png',
]);

it('strips directory components', function (): void {
    expect(ContentDispositionGuard::filename('/var/app/storage/kyc/rg.png'))->toBe('rg.png');
});

it('rejects a filename that breaks out of the Content-Disposition header', function (string $filename): void {
    ContentDispositionGuard::filename($filename);
})->throws(InvalidArgumentException::class, 'Quotes, backslashes and control characters are not allowed.')->with([
    'quote' => 'ok.png"; name="type',
    'trailing backslash' => 'ok.png\\',
    'inner backslash' => 'ok\\".png',
    'crlf' => "ok.png\"\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\nIDENTIFICATION",
    'lf only' => "ok.png\nX-Injected: 1",
    'cr only' => "ok.png\rX-Injected: 1",
    'nul' => "ok.png\0.exe",
    'del' => "ok.png\x7f",
]);

it('rejects an empty filename', function (): void {
    ContentDispositionGuard::filename('');
})->throws(InvalidArgumentException::class, 'Upload filename must not be empty');

it('rejects a filename that has no name component left after stripping directories', function (): void {
    ContentDispositionGuard::filename('/');
})->throws(InvalidArgumentException::class, 'Upload filename must not be empty');

it('accepts a filename at the exact 255-char limit', function (): void {
    $filename = str_repeat('a', 255);

    expect(ContentDispositionGuard::filename($filename))->toBe($filename);
});

it('rejects a filename one char over the limit without interpolating it', function (): void {
    expect(fn () => ContentDispositionGuard::filename(str_repeat('a', 256)))
        ->toThrow(InvalidArgumentException::class, 'The upload filename must be at most 255 chars; got 256.');
});

it('accepts the part names the SDK sends', function (string $partName): void {
    expect(ContentDispositionGuard::partName($partName))->toBe($partName);
})->with([
    'documentFile',
    'type',
    'certificateFile',
    'logoFile',
]);

it('keeps a part name whole instead of stripping directory components', function (): void {
    expect(ContentDispositionGuard::partName('a/b'))->toBe('a/b');
});

it('rejects a part name that breaks out of the Content-Disposition header', function (string $partName): void {
    ContentDispositionGuard::partName($partName);
})->throws(InvalidArgumentException::class, 'Quotes, backslashes and control characters are not allowed.')->with([
    'quote' => 'documentFile"; filename="evil.exe',
    'trailing backslash' => 'documentFile\\',
    'crlf' => "documentFile\r\nX-Injected: 1",
    'nul' => "documentFile\0",
]);

it('rejects an empty part name', function (): void {
    ContentDispositionGuard::partName('');
})->throws(InvalidArgumentException::class, 'Multipart part name must not be empty');

it('accepts a part name at the exact 255-char limit', function (): void {
    $partName = str_repeat('a', 255);

    expect(ContentDispositionGuard::partName($partName))->toBe($partName);
});

it('rejects a part name one char over the limit without interpolating it', function (): void {
    expect(fn () => ContentDispositionGuard::partName(str_repeat('a', 256)))
        ->toThrow(InvalidArgumentException::class, 'The multipart part name must be at most 255 chars; got 256.');
});

it('accepts part headers Guzzle can write verbatim', function (): void {
    // A header value is not a quoted string, so quotes and backslashes are
    // ordinary there — unlike in a Content-Disposition filename.
    $headers = ['Content-Type' => 'text/plain; charset="utf-8"'];

    expect(ContentDispositionGuard::partHeaders($headers))->toBe($headers);
});

it('accepts an empty header list', function (): void {
    expect(ContentDispositionGuard::partHeaders([]))->toBe([]);
});

it('rejects a control character in a part header value', function (string $value): void {
    // Guzzle writes each pair as "{$key}: {$value}\r\n" with no validation, so a
    // CR or LF closes the part's header block and appends whatever follows.
    expect(fn (): array => ContentDispositionGuard::partHeaders(['X-Meta' => $value]))
        ->toThrow(InvalidArgumentException::class, "Invalid value for multipart part header 'X-Meta'");
})->with([
    'crlf' => "ok\r\nX-Injected: 1",
    'lf' => "ok\nX-Injected: 1",
    'cr' => "ok\rX-Injected: 1",
    'nul' => "ok\0",
    'del' => "ok\x7F",
]);

it('rejects a part header name that is not an RFC 7230 token', function (string $name): void {
    expect(fn (): array => ContentDispositionGuard::partHeaders([$name => 'value']))
        ->toThrow(InvalidArgumentException::class, 'Invalid multipart part header name');
})->with([
    'crlf' => "X-Meta\r\nX-Injected",
    'colon' => 'X-Meta: injected',
    'space' => 'X Meta',
    'empty' => '',
    'quote' => 'X-"Meta"',
]);

it('accepts a numerically-named part header, which PHP hands over as an int key', function (): void {
    // `['5' => 'v']` becomes `[5 => 'v']`: the key reaches the guard as an int,
    // and preg_match() under strict_types would reject it uncast.
    expect(ContentDispositionGuard::partHeaders(['5' => 'digits']))->toBe([5 => 'digits']);
});

it('coerces a scalar header value and validates what will actually be sent', function (): void {
    // The HTTP client interpolates whatever it is given; validating the pre-cast
    // value would let a scalar reach the wire unchecked, and rejecting it
    // outright would be a regression against callers who pass an int length.
    expect(ContentDispositionGuard::partHeaders(['Content-Length' => 123]))
        ->toBe(['Content-Length' => '123']);
});
