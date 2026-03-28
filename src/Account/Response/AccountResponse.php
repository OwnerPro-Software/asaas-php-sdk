<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Response;

use OwnerPro\Asaas\Support\BaseResponse;

final class AccountResponse extends BaseResponse
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $loginEmail = null;

    public ?string $phone = null;

    public ?string $mobilePhone = null;

    public ?string $address = null;

    public ?string $addressNumber = null;

    public ?string $complement = null;

    public ?string $province = null;

    public ?string $postalCode = null;

    public ?string $cpfCnpj = null;

    public ?string $birthDate = null;

    public ?string $personType = null;

    public ?string $companyType = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $country = null;

    public ?string $tradingName = null;

    public ?string $site = null;

    public ?string $walletId = null;

    public ?string $accountNumber = null;

    public ?string $accessToken = null;

    public ?string $apiKey = null;

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        $attributes = $this->toArray();

        if (isset($attributes['apiKey']) && is_string($attributes['apiKey'])) {
            $attributes['apiKey'] = '***';
        }

        if (isset($attributes['accessToken']) && is_string($attributes['accessToken'])) {
            $attributes['accessToken'] = '***';
        }

        if (isset($attributes['cpfCnpj']) && is_string($attributes['cpfCnpj'])) {
            $attributes['cpfCnpj'] = str_repeat('*', max(0, strlen($attributes['cpfCnpj']) - 3)).substr($attributes['cpfCnpj'], -3);
        }

        if (isset($attributes['accountNumber']) && is_string($attributes['accountNumber'])) {
            $attributes['accountNumber'] = str_repeat('*', max(0, strlen($attributes['accountNumber']) - 2)).substr($attributes['accountNumber'], -2);
        }

        return $attributes;
    }
}
