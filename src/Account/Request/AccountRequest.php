<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class AccountRequest
{
    use HasArrayFactory;

    public function __construct(
        public string $name,
        public string $email,
        public string $cpfCnpj,
        public string $mobilePhone,
        public float $incomeValue,
        public string $address,
        public string $addressNumber,
        public string $province,
        public string $postalCode,
        public ?string $birthDate = null,
        public ?string $companyType = null,
        public ?string $phone = null,
        public ?string $complement = null,
        public ?string $tradingName = null,
        public ?string $site = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name', 'email', 'cpfCnpj', 'mobilePhone', 'incomeValue', 'address', 'addressNumber', 'province', 'postalCode'];
    }
}
