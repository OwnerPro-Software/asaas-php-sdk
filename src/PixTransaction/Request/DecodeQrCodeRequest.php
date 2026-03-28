<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class DecodeQrCodeRequest
{
    use HasArrayFactory;

    public function __construct(
        public string $payload,
        public ?float $changeValue = null,
        public ?string $expectedPaymentDate = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['payload'];
    }
}
