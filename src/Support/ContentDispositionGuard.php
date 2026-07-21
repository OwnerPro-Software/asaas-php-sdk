<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;

/**
 * Validates the two caller-supplied values Guzzle interpolates into a
 * multipart part's `Content-Disposition: form-data; name="..."; filename="..."`.
 *
 * Neither value is escaped on the way in, so both are breakout points. A
 * double quote closes the value outright, and a **trailing backslash** does the
 * same by escaping the closing quote — RFC 2616 reads `\"` as a quoted-pair, so
 * `filename="evil\"` leaves the parser consuming the bytes that follow as if
 * they were still part of the name. Control characters (CR/LF/NUL included)
 * append arbitrary part headers. A caller forwarding a browser-supplied upload
 * name would otherwise let the uploader forge extra form fields inside the
 * request body.
 */
final class ContentDispositionGuard
{
    /**
     * The characters that can break out of a quoted header value: the double
     * quote, the backslash that escapes it, and every control character.
     */
    private const string UNSAFE_PATTERN = '/["\\\\\x00-\x1F\x7F]/';

    /**
     * Directory components are stripped: the caller's local path is not Asaas's
     * business, and shipping it discloses the server's filesystem layout.
     *
     * An empty result is rejected too — Guzzle then falls back to the stream's
     * `uri` metadata and ships the absolute path of the local file, which is
     * the disclosure this strips in the first place.
     */
    public static function filename(string $filename): string
    {
        $name = basename($filename);

        if ($name === '') {
            throw new InvalidArgumentException(
                'Upload filename must not be empty: an empty name makes the HTTP client fall back to the local file path, leaking it to Asaas.',
            );
        }

        return self::guard($name, 'upload filename');
    }

    /**
     * The part name is the form field Asaas reads the part as (`documentFile`,
     * `type`), so it is not a path and keeps every character it was given.
     */
    public static function partName(string $partName): string
    {
        if ($partName === '') {
            throw new InvalidArgumentException(
                'Multipart part name must not be empty: Asaas identifies each part by this field name.',
            );
        }

        return self::guard($partName, 'multipart part name');
    }

    private static function guard(string $value, string $label): string
    {
        if (strlen($value) > 255) {
            throw new InvalidArgumentException(
                sprintf('The %s must be at most 255 chars; got %d.', $label, strlen($value)),
            );
        }

        if (preg_match(self::UNSAFE_PATTERN, $value) === 1) {
            throw new InvalidArgumentException(
                sprintf("Invalid %s: '%s'. Quotes, backslashes and control characters are not allowed.", $label, $value),
            );
        }

        return $value;
    }
}
