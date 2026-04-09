<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\CreditCard\Request\CreditCardRequest;
use OwnerPro\Asaas\CreditCard\Request\PreAuthConfigRequest;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;

final readonly class CreditCardResource
{
    private const string TOKENIZE = '/creditCard/tokenizeCreditCard';

    private const string PRE_AUTH_CONFIG = '/creditCard/preAuthorization/config';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreditCardRequest $data */
    public function tokenize(array|CreditCardRequest $data): AsaasResult
    {
        return $this->connector->post(self::TOKENIZE, CreditCardRequest::resolveData($data));
    }

    public function getPreAuthorizationConfig(): AsaasResult
    {
        return $this->connector->get(self::PRE_AUTH_CONFIG);
    }

    /** @param array<string, mixed>|PreAuthConfigRequest $data */
    public function setPreAuthorizationConfig(array|PreAuthConfigRequest $data): AsaasResult
    {
        return $this->connector->post(self::PRE_AUTH_CONFIG, PreAuthConfigRequest::resolveData($data));
    }
}
