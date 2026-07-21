<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;

final class IdGuard
{
    public static function validate(string $id): string
    {
        $length = strlen($id);

        if ($length === 0 || $length > 255) {
            throw new InvalidArgumentException(
                sprintf('Resource ID must be 1..255 chars; got %d.', $length),
            );
        }

        // `D` anchors `$` at the very end: without it PCRE also matches just
        // before a trailing newline, letting an untrimmed ID reach the URL.
        if (preg_match('/^[a-zA-Z0-9_-]+$/D', $id) !== 1) {
            throw new InvalidArgumentException(
                sprintf("Invalid resource ID: '%s'", $id),
            );
        }

        return $id;
    }
}
