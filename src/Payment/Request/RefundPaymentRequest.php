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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if (is_array($this->splitRefunds)) {
            $data['splitRefunds'] = array_map(
                fn (array|SplitRefund $item): array => $item instanceof SplitRefund ? $item->toArray() : $item,
                $this->splitRefunds,
            );
        }

        return $data;
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
