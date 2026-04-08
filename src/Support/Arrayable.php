<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

interface Arrayable
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
