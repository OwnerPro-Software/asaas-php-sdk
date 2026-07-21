<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Account\AccessTokenPermission;
use OwnerPro\Asaas\Account\AccessTokenScope;
use OwnerPro\Asaas\Account\CompanyType;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Support\Redactable;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use SensitiveParameter;

final readonly class AccountRequest implements JsonSerializable, Redactable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    /** @var list<CreateWebhookRequest>|null */
    public ?array $webhooks;

    public ?AccessTokenConfig $accessTokenConfig;

    /**
     * @param  list<array<string, mixed>|CreateWebhookRequest>|null  $webhooks
     * @param  array{name?: string, permissions?: list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|AccessTokenPermissionConfig>}|AccessTokenConfig|null  $accessTokenConfig
     * @param  string|null  $birthDate  Format `YYYY-MM-DD`.
     */
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
        public ?string $site = null,
        #[SensitiveParameter]
        public ?string $loginEmail = null,
        ?array $webhooks = null,
        array|AccessTokenConfig|null $accessTokenConfig = null,
    ) {
        $this->webhooks = $webhooks !== null ? array_map(
            // @phpstan-ignore argument.type
            fn (array|CreateWebhookRequest $item): CreateWebhookRequest => $item instanceof CreateWebhookRequest ? $item : CreateWebhookRequest::fromArray($item),
            $webhooks,
        ) : null;
        $this->accessTokenConfig = AccessTokenConfig::coerce($accessTokenConfig);
    }

    /** @return array{name: string, email: string, cpfCnpj: string, mobilePhone: string, incomeValue: float, address: string, addressNumber: string, province: string, postalCode: string, birthDate: ?string, companyType: CompanyType|string|null, phone: ?string, complement: ?string, site: ?string, loginEmail: ?string, webhooks: ?list<CreateWebhookRequest>, accessTokenConfig: ?AccessTokenConfig} */
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
            'site' => $this->site,
            'loginEmail' => $this->loginEmail !== null ? '***' : null,
            'webhooks' => $this->webhooks,
            'accessTokenConfig' => $this->accessTokenConfig,
        ];
    }

    /** @param array{name?: string, email?: string, cpfCnpj?: string, mobilePhone?: string, incomeValue?: float, address?: string, addressNumber?: string, province?: string, postalCode?: string, birthDate?: string, companyType?: CompanyType|string, phone?: string, complement?: string, site?: string, loginEmail?: string, webhooks?: list<array<string, mixed>|CreateWebhookRequest>, accessTokenConfig?: array{name?: string, permissions?: list<array{name?: AccessTokenPermission|string, scope?: AccessTokenScope|string}|AccessTokenPermissionConfig>}|AccessTokenConfig} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'] ?? throw new InvalidArgumentException('AccountRequest: name is required'),
            email: $data['email'] ?? throw new InvalidArgumentException('AccountRequest: email is required'),
            cpfCnpj: $data['cpfCnpj'] ?? throw new InvalidArgumentException('AccountRequest: cpfCnpj is required'),
            mobilePhone: $data['mobilePhone'] ?? throw new InvalidArgumentException('AccountRequest: mobilePhone is required'),
            incomeValue: $data['incomeValue'] ?? throw new InvalidArgumentException('AccountRequest: incomeValue is required'),
            address: $data['address'] ?? throw new InvalidArgumentException('AccountRequest: address is required'),
            addressNumber: $data['addressNumber'] ?? throw new InvalidArgumentException('AccountRequest: addressNumber is required'),
            province: $data['province'] ?? throw new InvalidArgumentException('AccountRequest: province is required'),
            postalCode: $data['postalCode'] ?? throw new InvalidArgumentException('AccountRequest: postalCode is required'),
            birthDate: $data['birthDate'] ?? null,
            companyType: $data['companyType'] ?? null,
            phone: $data['phone'] ?? null,
            complement: $data['complement'] ?? null,
            site: $data['site'] ?? null,
            loginEmail: $data['loginEmail'] ?? null,
            webhooks: $data['webhooks'] ?? null,
            accessTokenConfig: $data['accessTokenConfig'] ?? null,
        );
    }
}
