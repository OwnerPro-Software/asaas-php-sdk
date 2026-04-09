<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Pix\PixAddressKeyType;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class PixKeyRequest
{
    use HasArrayFactory;

    public function __construct(
        public PixAddressKeyType|string $type,
    ) {}

    /** @param array{type?: PixAddressKeyType|string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            type: $data['type'] ?? throw new InvalidArgumentException('PixKeyRequest: type is required'),
        );
    }
}
