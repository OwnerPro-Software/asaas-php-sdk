<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\FiscalInfo\Request;

use JsonSerializable;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Support\Missing;
use SensitiveParameter;

final readonly class FiscalInfoRequest implements JsonSerializable
{
    use HasUpdatableArrayFactory;
    use MasksSensitiveData;

    public function __construct(
        #[SensitiveParameter]
        public string|Missing $email = Missing::Value,
        public bool|Missing $simplesNacional = Missing::Value,
        public string|Missing $municipalInscription = Missing::Value,
        public bool|Missing $culturalProjectsPromoter = Missing::Value,
        public string|Missing $cnae = Missing::Value,
        public string|Missing $specialTaxRegime = Missing::Value,
        public string|Missing $serviceListItem = Missing::Value,
        public string|Missing $nbsCode = Missing::Value,
        public string|Missing $rpsSerie = Missing::Value,
        public int|Missing $rpsNumber = Missing::Value,
        public int|Missing $loteNumber = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $username = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $password = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $accessToken = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $certificatePassword = Missing::Value,
        public string|Missing $nationalPortalTaxCalculationRegime = Missing::Value,
    ) {}

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        /** @var array<string, mixed> $fields */
        $fields = array_filter(get_object_vars($this), fn (mixed $v): bool => ! $v instanceof Missing);

        foreach (['email', 'username', 'password', 'accessToken', 'certificatePassword'] as $secret) {
            if (array_key_exists($secret, $fields)) {
                $fields[$secret] = '***';
            }
        }

        return $fields;
    }

    /** @param array{email?: string, simplesNacional?: bool, municipalInscription?: string, culturalProjectsPromoter?: bool, cnae?: string, specialTaxRegime?: string, serviceListItem?: string, nbsCode?: string, rpsSerie?: string, rpsNumber?: int, loteNumber?: int, username?: string, password?: string, accessToken?: string, certificatePassword?: string, nationalPortalTaxCalculationRegime?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'] ?? Missing::Value,
            simplesNacional: $data['simplesNacional'] ?? Missing::Value,
            municipalInscription: $data['municipalInscription'] ?? Missing::Value,
            culturalProjectsPromoter: $data['culturalProjectsPromoter'] ?? Missing::Value,
            cnae: $data['cnae'] ?? Missing::Value,
            specialTaxRegime: $data['specialTaxRegime'] ?? Missing::Value,
            serviceListItem: $data['serviceListItem'] ?? Missing::Value,
            nbsCode: $data['nbsCode'] ?? Missing::Value,
            rpsSerie: $data['rpsSerie'] ?? Missing::Value,
            rpsNumber: $data['rpsNumber'] ?? Missing::Value,
            loteNumber: $data['loteNumber'] ?? Missing::Value,
            username: $data['username'] ?? Missing::Value,
            password: $data['password'] ?? Missing::Value,
            accessToken: $data['accessToken'] ?? Missing::Value,
            certificatePassword: $data['certificatePassword'] ?? Missing::Value,
            nationalPortalTaxCalculationRegime: $data['nationalPortalTaxCalculationRegime'] ?? Missing::Value,
        );
    }
}
