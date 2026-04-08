<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class Split implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public string $walletId,
        public ?float $fixedValue = null,
        public ?float $percentualValue = null,
        public ?float $totalFixedValue = null,
        public ?string $externalReference = null,
        public ?string $description = null,
    ) {}

    /** @param array{walletId?: string, fixedValue?: float, percentualValue?: float, totalFixedValue?: float, externalReference?: string, description?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            walletId: $data['walletId'] ?? throw new InvalidArgumentException('walletId is required'),
            fixedValue: $data['fixedValue'] ?? null,
            percentualValue: $data['percentualValue'] ?? null,
            totalFixedValue: $data['totalFixedValue'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
