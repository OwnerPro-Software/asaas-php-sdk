<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class Split
{
    use HasArrayFactory;

    public function __construct(
        public string $walletId,
        public ?float $fixedValue = null,
        public ?float $percentualValue = null,
        public ?float $totalFixedValue = null,
        public ?string $externalReference = null,
        public ?string $description = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['walletId'];
    }
}
