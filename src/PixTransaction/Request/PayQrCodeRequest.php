<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use OwnerPro\Asaas\Support\DTO\QrCodePayload;
use OwnerPro\Asaas\Support\HasArrayFactory;

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

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['qrCode', 'value'];
    }
}
