<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

trait MasksSensitiveData
{
    /** @return array<string, mixed> */
    abstract public function __debugInfo(): array;

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
