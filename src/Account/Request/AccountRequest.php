<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Account\CompanyType;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class AccountRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

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
            'cpfCnpj' => self::mask($this->cpfCnpj, 3),
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

    /** @param array{name?: string, email?: string, cpfCnpj?: string, mobilePhone?: string, incomeValue?: float, address?: string, addressNumber?: string, province?: string, postalCode?: string, birthDate?: string, companyType?: CompanyType|string, phone?: string, complement?: string, tradingName?: string, site?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? throw new InvalidArgumentException('name is required'),
            email: $data['email'] ?? throw new InvalidArgumentException('email is required'),
            cpfCnpj: $data['cpfCnpj'] ?? throw new InvalidArgumentException('cpfCnpj is required'),
            mobilePhone: $data['mobilePhone'] ?? throw new InvalidArgumentException('mobilePhone is required'),
            incomeValue: $data['incomeValue'] ?? throw new InvalidArgumentException('incomeValue is required'),
            address: $data['address'] ?? throw new InvalidArgumentException('address is required'),
            addressNumber: $data['addressNumber'] ?? throw new InvalidArgumentException('addressNumber is required'),
            province: $data['province'] ?? throw new InvalidArgumentException('province is required'),
            postalCode: $data['postalCode'] ?? throw new InvalidArgumentException('postalCode is required'),
            birthDate: $data['birthDate'] ?? null,
            companyType: $data['companyType'] ?? null,
            phone: $data['phone'] ?? null,
            complement: $data['complement'] ?? null,
            tradingName: $data['tradingName'] ?? null,
            site: $data['site'] ?? null,
        );
    }
}
