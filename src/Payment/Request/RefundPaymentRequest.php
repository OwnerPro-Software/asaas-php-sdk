<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\SplitRefund;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class RefundPaymentRequest
{
    use HasArrayFactory;

    /** @var list<SplitRefund>|null */
    public ?array $splitRefunds;

    /** @param list<array{id?: string, value?: float}|SplitRefund>|null $splitRefunds */
    public function __construct(
        public ?float $value = null,
        public ?string $description = null,
        ?array $splitRefunds = null,
    ) {
        $this->splitRefunds = $splitRefunds !== null ? array_map(
            fn (array|SplitRefund $item): SplitRefund => $item instanceof SplitRefund ? $item : SplitRefund::fromArray($item),
            $splitRefunds,
        ) : null;
    }

    /** @param array{value?: float, description?: string, splitRefunds?: list<array{id?: string, value?: float}>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? null,
            description: $data['description'] ?? null,
            splitRefunds: $data['splitRefunds'] ?? null,
        );
    }
}
