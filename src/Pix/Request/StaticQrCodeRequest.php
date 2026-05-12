<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix\Request;

use OwnerPro\Asaas\Pix\QrCodeFormat;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class StaticQrCodeRequest
{
    use HasArrayFactory;

    /**
     * @param  string|null  $expirationDate  Format `YYYY-MM-DD HH:MM:SS`.
     * @param  string|null  $externalReference  maxLength: 100 (validated server-side).
     */
    public function __construct(
        public ?string $addressKey = null,
        public ?string $description = null,
        public ?float $value = null,
        public QrCodeFormat|string|null $format = null,
        public ?string $expirationDate = null,
        public ?int $expirationSeconds = null,
        public ?bool $allowsMultiplePayments = null,
        public ?string $externalReference = null,
    ) {}

    /** @param array{addressKey?: string, description?: string, value?: float, format?: QrCodeFormat|string, expirationDate?: string, expirationSeconds?: int, allowsMultiplePayments?: bool, externalReference?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            addressKey: $data['addressKey'] ?? null,
            description: $data['description'] ?? null,
            value: $data['value'] ?? null,
            format: $data['format'] ?? null,
            expirationDate: $data['expirationDate'] ?? null,
            expirationSeconds: $data['expirationSeconds'] ?? null,
            allowsMultiplePayments: $data['allowsMultiplePayments'] ?? null,
            externalReference: $data['externalReference'] ?? null,
        );
    }
}
