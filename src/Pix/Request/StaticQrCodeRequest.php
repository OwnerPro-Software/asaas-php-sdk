<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class StaticQrCodeRequest
{
    use HasArrayFactory;

    public function __construct(
        public ?string $addressKey = null,
        public ?string $description = null,
        public ?float $value = null,
        public ?string $format = null,
        public ?string $expirationDate = null,
        public ?int $expirationSeconds = null,
        public ?bool $allowsMultiplePayments = null,
        public ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
