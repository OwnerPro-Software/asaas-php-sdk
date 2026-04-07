<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;

final readonly class CreditCardHolderInfo implements JsonSerializable
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

    public function jsonSerialize(): mixed
    {
        return $this->__debugInfo();
    }

    /** @param array{name?: string, email?: string, cpfCnpj?: string, postalCode?: string, addressNumber?: string, phone?: string, addressComplement?: string, mobilePhone?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? throw new InvalidArgumentException('name is required'),
            email: $data['email'] ?? throw new InvalidArgumentException('email is required'),
            cpfCnpj: $data['cpfCnpj'] ?? throw new InvalidArgumentException('cpfCnpj is required'),
            postalCode: $data['postalCode'] ?? throw new InvalidArgumentException('postalCode is required'),
            addressNumber: $data['addressNumber'] ?? throw new InvalidArgumentException('addressNumber is required'),
            phone: $data['phone'] ?? throw new InvalidArgumentException('phone is required'),
            addressComplement: $data['addressComplement'] ?? null,
            mobilePhone: $data['mobilePhone'] ?? null,
        );
    }
}
