<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use JsonSerializable;
use OwnerPro\Asaas\Account\CompanyType;
use OwnerPro\Asaas\Account\PersonType;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Support\Missing;
use OwnerPro\Asaas\Support\Redactable;
use SensitiveParameter;

final readonly class CommercialInfoRequest implements JsonSerializable, Redactable
{
    use HasUpdatableArrayFactory;
    use MasksSensitiveData;

    /** @param string|Missing $birthDate Format `YYYY-MM-DD`. */
    public function __construct(
        #[SensitiveParameter]
        public string|Missing $email = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $cpfCnpj = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $mobilePhone = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $phone = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $birthDate = Missing::Value,
        public CompanyType|string|Missing $companyType = Missing::Value,
        public float|Missing $incomeValue = Missing::Value,
        public string|Missing $address = Missing::Value,
        public string|Missing $addressNumber = Missing::Value,
        public string|Missing $complement = Missing::Value,
        public string|Missing $province = Missing::Value,
        public string|Missing $postalCode = Missing::Value,
        public string|Missing $site = Missing::Value,
        public PersonType|string|Missing $personType = Missing::Value,
        public string|Missing $companyName = Missing::Value,
    ) {}

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        /** @var array<string, mixed> $fields */
        $fields = array_filter(get_object_vars($this), fn (mixed $v): bool => ! $v instanceof Missing);

        if (array_key_exists('email', $fields)) {
            $fields['email'] = '***';
        }

        if (! $this->cpfCnpj instanceof Missing) {
            $fields['cpfCnpj'] = self::mask($this->cpfCnpj, 3);
        }

        if (array_key_exists('mobilePhone', $fields)) {
            $fields['mobilePhone'] = '***';
        }

        if (array_key_exists('phone', $fields)) {
            $fields['phone'] = '***';
        }

        if (array_key_exists('birthDate', $fields)) {
            $fields['birthDate'] = '***';
        }

        return $fields;
    }

    /** @param array{email?: string, cpfCnpj?: string, mobilePhone?: string, phone?: string, birthDate?: string, companyType?: CompanyType|string, incomeValue?: float, address?: string, addressNumber?: string, complement?: string, province?: string, postalCode?: string, site?: string, personType?: PersonType|string, companyName?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'] ?? Missing::Value,
            cpfCnpj: $data['cpfCnpj'] ?? Missing::Value,
            mobilePhone: $data['mobilePhone'] ?? Missing::Value,
            phone: $data['phone'] ?? Missing::Value,
            birthDate: $data['birthDate'] ?? Missing::Value,
            companyType: $data['companyType'] ?? Missing::Value,
            incomeValue: $data['incomeValue'] ?? Missing::Value,
            address: $data['address'] ?? Missing::Value,
            addressNumber: $data['addressNumber'] ?? Missing::Value,
            complement: $data['complement'] ?? Missing::Value,
            province: $data['province'] ?? Missing::Value,
            postalCode: $data['postalCode'] ?? Missing::Value,
            site: $data['site'] ?? Missing::Value,
            personType: $data['personType'] ?? Missing::Value,
            companyName: $data['companyName'] ?? Missing::Value,
        );
    }
}
