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

    /** @param array{value?: float, description?: string, splitRefunds?: list<array<string, mixed>>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? null,
            description: $data['description'] ?? null,
            splitRefunds: $data['splitRefunds'] ?? null,
        );
    }
}
