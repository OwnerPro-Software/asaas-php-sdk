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

    /** @return never */
    public function __serialize(): array
    {
        throw new LogicException(static::class.' cannot be serialized: it holds sensitive data that must never reach a queue, cache, or session payload. If you need a portable representation, call ->toArray() and serialize the array yourself.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return never
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException(static::class.' cannot be unserialized.');
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
