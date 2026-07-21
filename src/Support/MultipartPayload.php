<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;

final class MultipartPayload
{
    /**
     * Rejects a `$data` entry the HTTP client would promote to a file part.
     *
     * `PendingRequest::parseMultipartBodyFormat()` forwards any entry that is
     * an array carrying both `name` and `contents` straight through as a
     * multipart element — with its own `filename` and `headers`, none of which
     * pass {@see ContentDispositionGuard}. That is the whole guard bypassed by
     * writing the part in the wrong argument, so `$data` may not describe
     * parts: an entry that looks like one is refused rather than smuggled.
     *
     * A bare resource is refused for the other half of the same reason — the
     * client then falls back to the stream's `uri` metadata and ships the local
     * file's name, which is what the filename guard exists to stop.
     *
     * @param  array<mixed, mixed>  $data
     * @return array<mixed, mixed>
     */
    public static function rejectFileParts(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_resource($value)) {
                throw new InvalidArgumentException(sprintf(
                    "Multipart field '%s' is a file handle. Files belong in the \$files argument, where their name and filename are validated; a handle here reaches the wire carrying the local file's name.",
                    $key,
                ));
            }

            if (is_array($value) && isset($value['name'], $value['contents'])) {
                throw new InvalidArgumentException(sprintf(
                    "Multipart field '%s' describes a file part. Pass it in the \$files argument instead — a part described here bypasses the filename, part-name and header validation entirely.",
                    $key,
                ));
            }
        }

        return $data;
    }

    /**
     * Coerce PHP `bool` values in a multipart payload to the literal strings 'true' / 'false'.
     *
     * Guzzle's MultipartStream casts booleans via (string), turning `true` into `"1"` and
     * `false` into the empty string. Asaas's multipart endpoints expect the literal text,
     * so this normalization is applied before the request leaves the SDK.
     *
     * @param  array<mixed, mixed>  $data
     * @return array<mixed, mixed>
     */
    public static function stringifyBooleans(array $data): array
    {
        array_walk_recursive($data, static function (mixed &$value): void {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
        });

        return $data;
    }
}
