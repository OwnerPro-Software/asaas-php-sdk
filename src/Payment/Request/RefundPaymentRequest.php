<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\SplitRefund;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class RefundPaymentRequest
{
    use HasArrayFactory;

    /** @param list<array<string, mixed>|SplitRefund>|null $splitRefunds */
    public function __construct(
        public ?float $value = null,
        public ?string $description = null,
        public ?array $splitRefunds = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
