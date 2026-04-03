<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;
use TypeError;

final readonly class PreAuthConfigRequest
{
    use HasArrayFactory;

    public function __construct(
        public int $daysToExpire,
    ) {}

    /** @param array{daysToExpire?: int} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            daysToExpire: $data['daysToExpire'] ?? throw new TypeError('daysToExpire is required'),
        );
    }
}
