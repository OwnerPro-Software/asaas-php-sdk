<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use LogicException;

trait MasksSensitiveData
{
    /** @return array<string, mixed> */
    abstract public function __debugInfo(): array;

    public function __toString(): string
    {
        return static::class.'('.json_encode($this->__debugInfo(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).')';
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return $this->__debugInfo();
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        throw new LogicException(static::class.' cannot be unserialized: it is intentionally serialized as masked data to prevent leaking sensitive fields through caches, sessions, or queue payloads.');
    }

    public function jsonSerialize(): mixed
    {
        return $this->__debugInfo();
    }

    protected static function mask(string $value, int $visibleSuffix): string
    {
        if (strlen($value) <= $visibleSuffix) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', strlen($value) - $visibleSuffix).substr($value, -$visibleSuffix);
    }
}
