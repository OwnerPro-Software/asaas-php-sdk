<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

final readonly class Callback
{
    use HasArrayFactory;

    public function __construct(
        public string $successUrl,
        public ?bool $autoRedirect = null,
    ) {}

    /** @param array{successUrl?: string, autoRedirect?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            successUrl: $data['successUrl'] ?? throw new TypeError('successUrl is required'),
            autoRedirect: $data['autoRedirect'] ?? null,
        );
    }
}
