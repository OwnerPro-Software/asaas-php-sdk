<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class Callback
{
    use HasArrayFactory;

    public function __construct(
        public string $successUrl,
        public ?bool $autoRedirect = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['successUrl'];
    }
}
