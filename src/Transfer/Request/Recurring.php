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

    /**
     * @param  TransferRecurrenceFrequency|string  $frequency  Recurrence cadence. Determines the max for `$quantity`.
     * @param  int  $quantity  Number of repetitions, including the initial transfer.
     *                         Range: `1..51` for `WEEKLY`, `1..11` for `MONTHLY`.
     *                         Validated server-side; out-of-range values return HTTP 400.
     */
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
