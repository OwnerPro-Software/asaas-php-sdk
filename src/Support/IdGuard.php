<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;

final class IdGuard
{
    public static function validate(string $id): string
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $id) !== 1) {
            throw new InvalidArgumentException(
                sprintf("Invalid resource ID: '%s'", $id),
            );
        }

        return $id;
    }
}
