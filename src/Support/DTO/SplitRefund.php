<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class SplitRefund
{
    use HasArrayFactory;

    public function __construct(
        public string $id,
        public float $value,
    ) {}

    /** @param array{id?: string, value?: float} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? throw new InvalidArgumentException('id is required'),
            value: $data['value'] ?? throw new InvalidArgumentException('value is required'),
        );
    }
}
