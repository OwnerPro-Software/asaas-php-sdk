<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class Taxes
{
    use HasArrayFactory;

    public function __construct(
        public bool $retainIss,
        public float $iss,
        public float $pis,
        public float $cofins,
        public float $csll,
        public float $inss,
        public float $ir,
        public ?string $nbsCode = null,
        public ?string $taxSituationCode = null,
        public ?string $taxClassificationCode = null,
        public ?string $operationIndicatorCode = null,
        public ?string $pisCofinsRetentionType = null,
        public ?string $pisCofinsTaxStatus = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['retainIss', 'iss', 'pis', 'cofins', 'csll', 'inss', 'ir'];
    }
}
