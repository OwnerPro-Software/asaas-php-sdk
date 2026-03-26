<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use OwnerPro\Asaas\Support\DTO\QrCodePayload;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class PayQrCodeRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|QrCodePayload $qrCode */
    public function __construct(
        public readonly array|QrCodePayload $qrCode,
        public readonly float $value,
        public readonly ?string $description = null,
        public readonly ?string $scheduleDate = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if ($this->qrCode instanceof QrCodePayload) {
            $data['qrCode'] = $this->qrCode->toArray();
        }

        return $data;
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['qrCode', 'value'];
    }
}
