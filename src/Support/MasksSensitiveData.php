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

    /**
     * The fill is a constant width rather than one asterisk per hidden
     * character: a proportional mask publishes the exact length of the value it
     * hides, which narrows a brute-force search over a token and distinguishes
     * a CPF from a CNPJ. Length is free entropy to hand out, so it is not.
     *
     * Values no longer than `$visibleSuffix` are masked whole — a suffix cannot
     * be revealed without revealing the value. An empty value stays empty:
     * there is nothing to hide, and printing a fill for it would disguise an
     * unset field as a redacted one, hiding empty-payload bugs from the dump.
     */
    protected static function mask(string $value, int $visibleSuffix): string
    {
        if ($value === '') {
            return '';
        }

        $fill = str_repeat('*', 8);

        if (strlen($value) <= $visibleSuffix) {
            return $fill;
        }

        return $fill.substr($value, -$visibleSuffix);
    }
}
