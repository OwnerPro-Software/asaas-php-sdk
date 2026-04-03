<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

final readonly class Bank
{
    use HasArrayFactory;

    public function __construct(
        public string $code,
    ) {}

    /** @param array{code?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            code: $data['code'] ?? throw new TypeError('code is required'),
        );
    }
}
