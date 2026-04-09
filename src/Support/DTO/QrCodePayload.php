<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class QrCodePayload implements Arrayable
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
            payload: $data['payload'] ?? throw new InvalidArgumentException('QrCodePayload: payload is required'),
            changeValue: $data['changeValue'] ?? null,
        );
    }
}
