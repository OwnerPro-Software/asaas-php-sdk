<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateAccountRequest
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $cpfCnpj,
        public readonly string $mobilePhone,
        public readonly float $incomeValue,
        public readonly string $address,
        public readonly string $addressNumber,
        public readonly string $province,
        public readonly string $postalCode,
        public readonly ?string $birthDate = null,
        public readonly ?string $companyType = null,
        public readonly ?string $phone = null,
        public readonly ?string $complement = null,
        public readonly ?string $tradingName = null,
        public readonly ?string $site = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name', 'email', 'cpfCnpj', 'mobilePhone', 'incomeValue', 'address', 'addressNumber', 'province', 'postalCode'];
    }
}
