<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;

/**
 * Validates the caller-supplied values Guzzle interpolates into a multipart
 * part's headers — the `name` and `filename` of
 * `Content-Disposition: form-data; name="..."; filename="..."`, and any extra
 * header the caller hangs on the part.
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

    /** RFC 7230 `token`: the character set a header field name is drawn from. */
    private const string HEADER_NAME_PATTERN = '/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D';

    /** CR, LF and NUL included: any of them ends the header line Guzzle is writing. */
    private const string CONTROL_PATTERN = '/[\x00-\x1F\x7F]/';

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

    /**
     * Validates the extra headers a caller can hang on a part.
     *
     * Guzzle writes them into the part preamble as `"{$key}: {$value}\r\n"`
     * with no validation of its own, so a CR or LF in either half closes the
     * header block early and appends whatever follows as further headers — or
     * as a whole extra part. The double quote and backslash are *not* rejected
     * here, unlike in a `Content-Disposition` value: a header value is not a
     * quoted string, and `charset="utf-8"` is ordinary.
     *
     * Names and values are cast to string before they are checked, and the cast
     * result is what gets returned: the HTTP client interpolates them into the
     * preamble the same way, so validating the pre-cast value would leave a
     * scalar reaching the wire unchecked. PHP also narrows a numeric array key
     * to `int`, so a header literally named `5` arrives as one.
     *
     * @param  array<string, string|int|float|bool>  $headers
     * @return array<string, string>
     */
    public static function partHeaders(array $headers): array
    {
        $validated = [];

        foreach ($headers as $key => $value) {
            $name = (string) $key;

            if (preg_match(self::HEADER_NAME_PATTERN, $name) !== 1) {
                throw new InvalidArgumentException(
                    sprintf("Invalid multipart part header name: '%s'. Header names are RFC 7230 tokens.", $name),
                );
            }

            $stringValue = (string) $value;

            if (preg_match(self::CONTROL_PATTERN, $stringValue) === 1) {
                throw new InvalidArgumentException(
                    sprintf("Invalid value for multipart part header '%s': control characters are not allowed.", $name),
                );
            }

            $validated[$name] = $stringValue;
        }

        return $validated;
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
