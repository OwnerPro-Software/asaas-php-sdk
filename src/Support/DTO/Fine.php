<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Payment\FineType;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class Fine implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public float $value,
        public FineType|string|null $type = null,
    ) {}

    /** @param array{value?: float, type?: FineType|string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('Fine: value is required'),
            type: $data['type'] ?? null,
        );
    }

    /** @param array{value?: float, type?: FineType|string}|Fine|float|Missing|null $value */
    public static function coerce(array|self|float|Missing|null $value): self|Missing|null
    {
        return match (true) {
            $value === null, $value instanceof Missing, $value instanceof self => $value,
            is_array($value) => self::fromArray($value),
            default => new self(value: $value),
        };
    }
}
