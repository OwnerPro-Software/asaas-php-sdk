<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class UpdatePaymentRequest
{
    use HasArrayFactory;

    /** @param list<array<string, mixed>|Split>|null $split */
    public function __construct(
        public readonly ?string $billingType = null,
        public readonly ?float $value = null,
        public readonly ?string $dueDate = null,
        public readonly ?string $description = null,
        public readonly ?string $externalReference = null,
        public readonly ?float $discount = null,
        public readonly ?float $interest = null,
        public readonly ?float $fine = null,
        public readonly ?bool $postalService = null,
        public readonly ?array $split = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if (is_array($this->split)) {
            $data['split'] = array_map(
                fn (array|Split $item): array => $item instanceof Split ? $item->toArray() : $item,
                $this->split,
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
