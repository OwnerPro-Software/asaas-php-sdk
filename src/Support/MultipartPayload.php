<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final class MultipartPayload
{
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
