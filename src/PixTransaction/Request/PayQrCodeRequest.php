<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\DTO\QrCodePayload;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class PayQrCodeRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public QrCodePayload $qrCode;

    /**
     * @param  array{payload?: string, changeValue?: float}|QrCodePayload  $qrCode
     * @param  string|null  $scheduleDate  Format `YYYY-MM-DD`.
     */
    public function __construct(
        #[SensitiveParameter]
        array|QrCodePayload $qrCode,
        public float $value,
        public ?string $description = null,
        public ?string $scheduleDate = null,
    ) {
        $this->qrCode = is_array($qrCode) ? QrCodePayload::fromArray($qrCode) : $qrCode;
    }

    /** @return array{qrCode: array{payload: string, changeValue: ?float}, value: float, description: ?string, scheduleDate: ?string} */
    public function __debugInfo(): array
    {
        return [
            'qrCode' => [
                'payload' => self::mask($this->qrCode->payload, 8),
                'changeValue' => $this->qrCode->changeValue,
            ],
            'value' => $this->value,
            'description' => $this->description,
            'scheduleDate' => $this->scheduleDate,
        ];
    }

    /** @param array{qrCode?: array{payload?: string, changeValue?: float}, value?: float, description?: string, scheduleDate?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            qrCode: $data['qrCode'] ?? throw new InvalidArgumentException('PayQrCodeRequest: qrCode is required'),
            value: $data['value'] ?? throw new InvalidArgumentException('PayQrCodeRequest: value is required'),
            description: $data['description'] ?? null,
            scheduleDate: $data['scheduleDate'] ?? null,
        );
    }
}
