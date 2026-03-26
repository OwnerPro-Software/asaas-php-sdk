<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class DecodeQrCodeRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $payload,
        public readonly ?float $changeValue = null,
        public readonly ?string $expectedPaymentDate = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['payload'];
    }
}
