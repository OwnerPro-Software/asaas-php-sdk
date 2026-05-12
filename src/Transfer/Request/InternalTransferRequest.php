<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class InternalTransferRequest
{
    use HasArrayFactory;

    public function __construct(
        public float $value,
        public string $walletId,
        public ?string $externalReference = null,
    ) {}

    /** @param array{value?: float, walletId?: string, externalReference?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('InternalTransferRequest: value is required'),
            walletId: $data['walletId'] ?? throw new InvalidArgumentException('InternalTransferRequest: walletId is required'),
            externalReference: $data['externalReference'] ?? null,
        );
    }
}
