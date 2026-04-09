<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

trait HasUpdatableArrayFactory
{
    use HasArrayFactory;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $vars */
        $vars = array_filter(get_object_vars($this), fn (mixed $v): bool => ! $v instanceof Missing);

        return $this->convertProperties($vars);
    }
}
