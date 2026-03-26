<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreditCard
{
    use HasArrayFactory;

    public function __construct(
        public string $holderName,
        public string $number,
        public string $expiryMonth,
        public string $expiryYear,
        public string $ccv,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['holderName', 'number', 'expiryMonth', 'expiryYear', 'ccv'];
    }
}
