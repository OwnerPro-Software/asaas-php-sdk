<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class CreditCard implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

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
            holderName: $data['holderName'] ?? throw new InvalidArgumentException('holderName is required'),
            number: $data['number'] ?? throw new InvalidArgumentException('number is required'),
            expiryMonth: $data['expiryMonth'] ?? throw new InvalidArgumentException('expiryMonth is required'),
            expiryYear: $data['expiryYear'] ?? throw new InvalidArgumentException('expiryYear is required'),
            ccv: $data['ccv'] ?? throw new InvalidArgumentException('ccv is required'),
        );
    }
}
