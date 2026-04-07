<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
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

    /** @param array{retainIss?: bool, iss?: float, pis?: float, cofins?: float, csll?: float, inss?: float, ir?: float, nbsCode?: string, taxSituationCode?: string, taxClassificationCode?: string, operationIndicatorCode?: string, pisCofinsRetentionType?: string, pisCofinsTaxStatus?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            retainIss: $data['retainIss'] ?? throw new InvalidArgumentException('retainIss is required'),
            iss: $data['iss'] ?? throw new InvalidArgumentException('iss is required'),
            pis: $data['pis'] ?? throw new InvalidArgumentException('pis is required'),
            cofins: $data['cofins'] ?? throw new InvalidArgumentException('cofins is required'),
            csll: $data['csll'] ?? throw new InvalidArgumentException('csll is required'),
            inss: $data['inss'] ?? throw new InvalidArgumentException('inss is required'),
            ir: $data['ir'] ?? throw new InvalidArgumentException('ir is required'),
            nbsCode: $data['nbsCode'] ?? null,
            taxSituationCode: $data['taxSituationCode'] ?? null,
            taxClassificationCode: $data['taxClassificationCode'] ?? null,
            operationIndicatorCode: $data['operationIndicatorCode'] ?? null,
            pisCofinsRetentionType: $data['pisCofinsRetentionType'] ?? null,
            pisCofinsTaxStatus: $data['pisCofinsTaxStatus'] ?? null,
        );
    }
}
