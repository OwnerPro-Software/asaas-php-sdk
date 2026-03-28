<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment\Request;

use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class UpdatePaymentRequest
{
    use HasArrayFactory;

    /** @param list<array<string, mixed>|Split>|null $split */
    public function __construct(
        public ?string $billingType = null,
        public ?float $value = null,
        public ?string $dueDate = null,
        public ?string $description = null,
        public ?string $externalReference = null,
        public ?float $discount = null,
        public ?float $interest = null,
        public ?float $fine = null,
        public ?bool $postalService = null,
        public ?array $split = null,
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
