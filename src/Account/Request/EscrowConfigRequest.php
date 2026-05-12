<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class EscrowConfigRequest
{
    use HasUpdatableArrayFactory;

    public function __construct(
        public int $daysToExpire,
        public bool|Missing $enabled = Missing::Value,
        public bool|Missing $isFeePayer = Missing::Value,
    ) {}

    /** @param array{daysToExpire: int, enabled?: bool, isFeePayer?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            daysToExpire: $data['daysToExpire'],
            enabled: $data['enabled'] ?? Missing::Value,
            isFeePayer: $data['isFeePayer'] ?? Missing::Value,
        );
    }
}
