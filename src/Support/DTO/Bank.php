<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class Bank implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public string $code,
    ) {}

    /** @param array{code?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            code: $data['code'] ?? throw new InvalidArgumentException('Bank: code is required'),
        );
    }
}
