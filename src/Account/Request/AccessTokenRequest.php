<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class AccessTokenRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $name = null,
        public ?bool $enabled = null,
        public ?string $expirationDate = null,
    ) {}

    /** @param array{name?: string, enabled?: bool, expirationDate?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? null,
            enabled: $data['enabled'] ?? null,
            expirationDate: $data['expirationDate'] ?? null,
        );
    }
}
