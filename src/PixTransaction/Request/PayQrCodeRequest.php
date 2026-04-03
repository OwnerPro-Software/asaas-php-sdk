<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use OwnerPro\Asaas\Support\DTO\QrCodePayload;
use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

final readonly class PayQrCodeRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|QrCodePayload $qrCode */
    public function __construct(
        public array|QrCodePayload $qrCode,
        public float $value,
        public ?string $description = null,
        public ?string $scheduleDate = null,
    ) {}

    /** @param array{qrCode?: array{payload?: string, changeValue?: float}, value?: float, description?: string, scheduleDate?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            qrCode: QrCodePayload::fromArray($data['qrCode'] ?? []),
            value: $data['value'] ?? throw new TypeError('value is required'),
            description: $data['description'] ?? null,
            scheduleDate: $data['scheduleDate'] ?? null,
        );
    }
}
