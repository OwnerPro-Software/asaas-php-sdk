<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class DecodeQrCodeRequest
{
    use HasArrayFactory;

    public function __construct(
        public string $payload,
        public ?float $changeValue = null,
        public ?string $expectedPaymentDate = null,
    ) {}

    /** @param array{payload?: string, changeValue?: float, expectedPaymentDate?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            payload: $data['payload'] ?? throw new InvalidArgumentException('payload is required'),
            changeValue: $data['changeValue'] ?? null,
            expectedPaymentDate: $data['expectedPaymentDate'] ?? null,
        );
    }
}
