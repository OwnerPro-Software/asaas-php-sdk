<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;
use TypeError;

final readonly class CreditCard
{
    use HasArrayFactory;

    public function __construct(
        public string $holderName,
        #[SensitiveParameter]
        public string $number,
        public string $expiryMonth,
        public string $expiryYear,
        #[SensitiveParameter]
        public string $ccv,
    ) {}

    /** @return array{holderName: string, number: string, expiryMonth: string, expiryYear: string, ccv: string} */
    public function __debugInfo(): array
    {
        return [
            'holderName' => $this->holderName,
            'number' => str_repeat('*', max(0, strlen($this->number) - 4)).substr($this->number, -4),
            'expiryMonth' => $this->expiryMonth,
            'expiryYear' => $this->expiryYear,
            'ccv' => '***',
        ];
    }

    /** @param array{holderName?: string, number?: string, expiryMonth?: string, expiryYear?: string, ccv?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            holderName: $data['holderName'] ?? throw new TypeError('holderName is required'),
            number: $data['number'] ?? throw new TypeError('number is required'),
            expiryMonth: $data['expiryMonth'] ?? throw new TypeError('expiryMonth is required'),
            expiryYear: $data['expiryYear'] ?? throw new TypeError('expiryYear is required'),
            ccv: $data['ccv'] ?? throw new TypeError('ccv is required'),
        );
    }
}
