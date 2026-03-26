<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class StaticQrCodeRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly ?string $addressKey = null,
        public readonly ?string $description = null,
        public readonly ?float $value = null,
        public readonly ?string $format = null,
        public readonly ?string $expirationDate = null,
        public readonly ?int $expirationSeconds = null,
        public readonly ?bool $allowsMultiplePayments = null,
        public readonly ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
