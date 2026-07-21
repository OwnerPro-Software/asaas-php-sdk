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
