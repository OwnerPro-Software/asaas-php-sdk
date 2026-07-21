<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;

/**
 * Validates the `filename` of a multipart part before it reaches Guzzle.
 *
 * Guzzle interpolates the value straight into
 * `Content-Disposition: form-data; name="..."; filename="..."` without escaping
 * anything, so a filename carrying `"` or CRLF closes the header and appends
 * arbitrary part headers — a caller forwarding a browser-supplied upload name
 * would let the uploader forge extra form fields inside the request body.
 *
 * An empty filename is rejected too: Guzzle then falls back to the stream's
 * `uri` metadata and ships the absolute server path of the local file.
 */
final class FilenameGuard
{
    public static function validate(string $filename): string
    {
        $name = basename($filename);

        if ($name === '') {
            throw new InvalidArgumentException(
                'Upload filename must not be empty: an empty name makes the HTTP client fall back to the local file path, leaking it to Asaas.',
            );
        }

        if (strlen($name) > 255) {
            throw new InvalidArgumentException(
                sprintf('Upload filename must be at most 255 chars; got %d.', strlen($name)),
            );
        }

        // Control characters (CR/LF/NUL included) and the double quote are the
        // characters that can break out of the quoted header value.
        if (preg_match('/["\x00-\x1F\x7F]/', $name) === 1) {
            throw new InvalidArgumentException(
                sprintf("Invalid upload filename: '%s'. Quotes and control characters are not allowed.", $name),
            );
        }

        return $name;
    }
}
