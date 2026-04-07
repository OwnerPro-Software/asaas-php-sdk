<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use JsonSerializable;
use OwnerPro\Asaas\Account\CompanyType;
use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;
use TypeError;

final readonly class AccountRequest implements JsonSerializable
{
    use HasArrayFactory;

    public function __construct(
        public string $name,
        #[SensitiveParameter]
        public string $email,
        #[SensitiveParameter]
        public string $cpfCnpj,
        #[SensitiveParameter]
        public string $mobilePhone,
        public float $incomeValue,
        public string $address,
        public string $addressNumber,
        public string $province,
        public string $postalCode,
        #[SensitiveParameter]
        public ?string $birthDate = null,
        public CompanyType|string|null $companyType = null,
        #[SensitiveParameter]
        public ?string $phone = null,
        public ?string $complement = null,
        public ?string $tradingName = null,
        public ?string $site = null,
    ) {}

    /** @return array{name: string, email: string, cpfCnpj: string, mobilePhone: string, incomeValue: float, address: string, addressNumber: string, province: string, postalCode: string, birthDate: ?string, companyType: CompanyType|string|null, phone: ?string, complement: ?string, tradingName: ?string, site: ?string} */
    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'email' => '***',
            'cpfCnpj' => str_repeat('*', max(0, strlen($this->cpfCnpj) - 3)).substr($this->cpfCnpj, -3),
            'mobilePhone' => '***',
            'incomeValue' => $this->incomeValue,
            'address' => $this->address,
            'addressNumber' => $this->addressNumber,
            'province' => $this->province,
            'postalCode' => $this->postalCode,
            'birthDate' => $this->birthDate !== null ? '***' : null,
            'companyType' => $this->companyType,
            'phone' => $this->phone !== null ? '***' : null,
            'complement' => $this->complement,
            'tradingName' => $this->tradingName,
            'site' => $this->site,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->__debugInfo();
    }

    /** @param array{name?: string, email?: string, cpfCnpj?: string, mobilePhone?: string, incomeValue?: float, address?: string, addressNumber?: string, province?: string, postalCode?: string, birthDate?: string, companyType?: CompanyType|string, phone?: string, complement?: string, tradingName?: string, site?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? throw new TypeError('name is required'),
            email: $data['email'] ?? throw new TypeError('email is required'),
            cpfCnpj: $data['cpfCnpj'] ?? throw new TypeError('cpfCnpj is required'),
            mobilePhone: $data['mobilePhone'] ?? throw new TypeError('mobilePhone is required'),
            incomeValue: $data['incomeValue'] ?? throw new TypeError('incomeValue is required'),
            address: $data['address'] ?? throw new TypeError('address is required'),
            addressNumber: $data['addressNumber'] ?? throw new TypeError('addressNumber is required'),
            province: $data['province'] ?? throw new TypeError('province is required'),
            postalCode: $data['postalCode'] ?? throw new TypeError('postalCode is required'),
            birthDate: $data['birthDate'] ?? null,
            companyType: $data['companyType'] ?? null,
            phone: $data['phone'] ?? null,
            complement: $data['complement'] ?? null,
            tradingName: $data['tradingName'] ?? null,
            site: $data['site'] ?? null,
        );
    }
}
