<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;

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

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['holderName', 'number', 'expiryMonth', 'expiryYear', 'ccv'];
    }
}
