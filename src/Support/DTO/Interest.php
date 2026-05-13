<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class Interest implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public float $value,
    ) {}

    /** @param array{value?: float} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('Interest: value is required'),
        );
    }

    /** @param array{value?: float}|Interest|float|Missing $value */
    public static function coerce(array|self|float|Missing $value): self|Missing
    {
        if ($value instanceof Missing || $value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            return self::fromArray($value);
        }

        return new self(value: $value);
    }
}
