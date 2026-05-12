<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Transfer\TransferRecurrenceFrequency;

final readonly class Recurring implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public TransferRecurrenceFrequency|string $frequency,
        public int $quantity,
    ) {}

    /** @param array{frequency?: TransferRecurrenceFrequency|string, quantity?: int} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            frequency: $data['frequency'] ?? throw new InvalidArgumentException('Recurring: frequency is required'),
            quantity: $data['quantity'] ?? throw new InvalidArgumentException('Recurring: quantity is required'),
        );
    }
}
