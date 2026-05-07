<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixAutomatic\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class ImmediateQrCode implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public int $expirationSeconds,
        public float $originalValue,
        public ?string $pixKey = null,
        public ?string $description = null,
    ) {}

    /** @param array{expirationSeconds?: int, originalValue?: float, pixKey?: string, description?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            expirationSeconds: $data['expirationSeconds'] ?? throw new InvalidArgumentException('ImmediateQrCode: expirationSeconds is required'),
            originalValue: $data['originalValue'] ?? throw new InvalidArgumentException('ImmediateQrCode: originalValue is required'),
            pixKey: $data['pixKey'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
