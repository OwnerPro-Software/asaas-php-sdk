<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class PaymentCheckoutConfigRequest
{
    use HasUpdatableArrayFactory;

    /**
     * @param  bool|Missing  $enabled  Indicates whether the invoice checkout customization is active.
     *                                 Default server-side: `true`. The request schema in `my-account.json`
     *                                 does not declare a default for `enabled`, but the response schema
     *                                 (GET/POST `/v3/myAccount/paymentCheckoutConfig/`) marks
     *                                 `default: true`. Omitting the field (`Missing::Value`) lets Asaas
     *                                 enable the customization; pass `false` explicitly to disable it.
     */
    public function __construct(
        public string $logoBackgroundColor,
        public string $infoBackgroundColor,
        public string $fontColor,
        public bool|Missing $enabled = Missing::Value,
    ) {}

    /** @param array{logoBackgroundColor?: string, infoBackgroundColor?: string, fontColor?: string, enabled?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            logoBackgroundColor: $data['logoBackgroundColor'] ?? throw new InvalidArgumentException('PaymentCheckoutConfigRequest: logoBackgroundColor is required'),
            infoBackgroundColor: $data['infoBackgroundColor'] ?? throw new InvalidArgumentException('PaymentCheckoutConfigRequest: infoBackgroundColor is required'),
            fontColor: $data['fontColor'] ?? throw new InvalidArgumentException('PaymentCheckoutConfigRequest: fontColor is required'),
            enabled: $data['enabled'] ?? Missing::Value,
        );
    }
}
