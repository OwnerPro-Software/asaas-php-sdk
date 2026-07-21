<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\FilenameGuard;

mutates(FilenameGuard::class);

it('accepts ordinary upload filenames', function (string $filename): void {
    expect(FilenameGuard::validate($filename))->toBe($filename);
})->with([
    'rg.png',
    'contrato social.pdf',
    'certidão-negativa.pdf',
    'logo.png',
    str_repeat('a', 251).'.png',
]);

it('strips directory components', function (): void {
    expect(FilenameGuard::validate('/var/app/storage/kyc/rg.png'))->toBe('rg.png');
});

it('rejects a filename that breaks out of the Content-Disposition header', function (string $filename): void {
    FilenameGuard::validate($filename);
})->throws(InvalidArgumentException::class, 'Quotes and control characters are not allowed.')->with([
    'quote' => 'ok.png"; name="type',
    'crlf' => "ok.png\"\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\nIDENTIFICATION",
    'lf only' => "ok.png\nX-Injected: 1",
    'cr only' => "ok.png\rX-Injected: 1",
    'nul' => "ok.png\0.exe",
    'del' => "ok.png\x7f",
]);

it('rejects an empty filename', function (): void {
    FilenameGuard::validate('');
})->throws(InvalidArgumentException::class, 'Upload filename must not be empty');

it('rejects a filename that has no name component left after stripping directories', function (): void {
    FilenameGuard::validate('/');
})->throws(InvalidArgumentException::class, 'Upload filename must not be empty');

it('accepts a filename at the exact 255-char limit', function (): void {
    $filename = str_repeat('a', 255);

    expect(FilenameGuard::validate($filename))->toBe($filename);
});

it('rejects a filename one char over the limit without interpolating it', function (): void {
    expect(fn () => FilenameGuard::validate(str_repeat('a', 256)))
        ->toThrow(InvalidArgumentException::class, 'Upload filename must be at most 255 chars; got 256.');
});
