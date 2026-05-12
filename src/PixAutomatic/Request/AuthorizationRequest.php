<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixAutomatic\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticFrequency;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class AuthorizationRequest
{
    use HasArrayFactory;

    public ImmediateQrCode $immediateQrCode;

    /**
     * @param  array{expirationSeconds?: int, originalValue?: float, pixKey?: string, description?: string}|ImmediateQrCode  $immediateQrCode
     * @param  string  $contractId  maxLength: 35 (validated server-side).
     * @param  string  $startDate  Format `YYYY-MM-DD`.
     * @param  string|null  $finishDate  Format `YYYY-MM-DD`.
     * @param  string|null  $description  maxLength: 35 (validated server-side).
     */
    public function __construct(
        public PixAutomaticFrequency|string $frequency,
        public string $contractId,
        public string $startDate,
        public string $customerId,
        array|ImmediateQrCode $immediateQrCode,
        public ?string $finishDate = null,
        public ?float $value = null,
        public ?string $description = null,
        public ?float $minLimitValue = null,
    ) {
        $this->immediateQrCode = is_array($immediateQrCode) ? ImmediateQrCode::fromArray($immediateQrCode) : $immediateQrCode;
    }

    /** @param array{frequency?: PixAutomaticFrequency|string, contractId?: string, startDate?: string, customerId?: string, immediateQrCode?: array{expirationSeconds?: int, originalValue?: float, pixKey?: string, description?: string}|ImmediateQrCode, finishDate?: string, value?: float, description?: string, minLimitValue?: float} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            frequency: $data['frequency'] ?? throw new InvalidArgumentException('AuthorizationRequest: frequency is required'),
            contractId: $data['contractId'] ?? throw new InvalidArgumentException('AuthorizationRequest: contractId is required'),
            startDate: $data['startDate'] ?? throw new InvalidArgumentException('AuthorizationRequest: startDate is required'),
            customerId: $data['customerId'] ?? throw new InvalidArgumentException('AuthorizationRequest: customerId is required'),
            immediateQrCode: $data['immediateQrCode'] ?? throw new InvalidArgumentException('AuthorizationRequest: immediateQrCode is required'),
            finishDate: $data['finishDate'] ?? null,
            value: $data['value'] ?? null,
            description: $data['description'] ?? null,
            minLimitValue: $data['minLimitValue'] ?? null,
        );
    }
}
