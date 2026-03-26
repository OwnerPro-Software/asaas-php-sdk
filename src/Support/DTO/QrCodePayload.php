<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class QrCodePayload
{
    use HasArrayFactory;

    public function __construct(
        public string $payload,
        public ?float $changeValue = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['payload'];
    }
}
