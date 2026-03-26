<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class CreditCardHolderInfo
{
    use HasArrayFactory;

    public function __construct(
        public string $name,
        public string $email,
        public string $cpfCnpj,
        public string $postalCode,
        public string $addressNumber,
        public string $phone,
        public ?string $addressComplement = null,
        public ?string $mobilePhone = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name', 'email', 'cpfCnpj', 'postalCode', 'addressNumber', 'phone'];
    }
}
