<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\DTO\QrCodePayload;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class PayQrCodeRequest
{
    use HasArrayFactory;

    public QrCodePayload $qrCode;

    /** @param array{payload?: string, changeValue?: float}|QrCodePayload $qrCode */
    public function __construct(
        array|QrCodePayload $qrCode,
        public float $value,
        public ?string $description = null,
        public ?string $scheduleDate = null,
    ) {
        $this->qrCode = is_array($qrCode) ? QrCodePayload::fromArray($qrCode) : $qrCode;
    }

    /** @param array{qrCode?: array{payload?: string, changeValue?: float}, value?: float, description?: string, scheduleDate?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            qrCode: QrCodePayload::fromArray($data['qrCode'] ?? throw new InvalidArgumentException('PayQrCodeRequest: qrCode is required')),
            value: $data['value'] ?? throw new InvalidArgumentException('PayQrCodeRequest: value is required'),
            description: $data['description'] ?? null,
            scheduleDate: $data['scheduleDate'] ?? null,
        );
    }
}
