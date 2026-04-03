<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

final readonly class QrCodePayload
{
    use HasArrayFactory;

    public function __construct(
        public string $payload,
        public ?float $changeValue = null,
    ) {}

    /** @param array{payload?: string, changeValue?: float} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            payload: $data['payload'] ?? throw new TypeError('payload is required'),
            changeValue: $data['changeValue'] ?? null,
        );
    }
}
