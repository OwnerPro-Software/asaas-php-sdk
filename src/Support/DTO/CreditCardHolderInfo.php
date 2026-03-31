<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;

final readonly class CreditCardHolderInfo
{
    use HasArrayFactory;

    public function __construct(
        public string $name,
        #[SensitiveParameter]
        public string $email,
        #[SensitiveParameter]
        public string $cpfCnpj,
        public string $postalCode,
        public string $addressNumber,
        #[SensitiveParameter]
        public string $phone,
        public ?string $addressComplement = null,
        #[SensitiveParameter]
        public ?string $mobilePhone = null,
    ) {}

    /** @return array{name: string, email: string, cpfCnpj: string, postalCode: string, addressNumber: string, phone: string, addressComplement: ?string, mobilePhone: ?string} */
    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'email' => '***',
            'cpfCnpj' => str_repeat('*', max(0, strlen($this->cpfCnpj) - 3)).substr($this->cpfCnpj, -3),
            'postalCode' => $this->postalCode,
            'addressNumber' => $this->addressNumber,
            'phone' => '***',
            'addressComplement' => $this->addressComplement,
            'mobilePhone' => $this->mobilePhone !== null ? '***' : null,
        ];
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name', 'email', 'cpfCnpj', 'postalCode', 'addressNumber', 'phone'];
    }
}
