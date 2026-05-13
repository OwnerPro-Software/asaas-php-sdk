<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Payment\DiscountType;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class Discount implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public float $value,
        public ?int $dueDateLimitDays = null,
        public DiscountType|string|null $type = null,
    ) {}

    /** @param array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('Discount: value is required'),
            dueDateLimitDays: $data['dueDateLimitDays'] ?? null,
            type: $data['type'] ?? null,
        );
    }

    /** @param array{value?: float, dueDateLimitDays?: int, type?: DiscountType|string}|Discount|float|Missing $value */
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
