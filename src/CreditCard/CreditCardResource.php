<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\CreditCard\Request\CreditCardRequest;
use OwnerPro\Asaas\CreditCard\Request\PreAuthConfigRequest;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\HttpConnector;

final readonly class CreditCardResource
{
    public function __construct(private HttpConnector $httpConnector) {}

    /** @param array<string, mixed>|CreditCardRequest $data */
    public function tokenize(array|CreditCardRequest $data): AsaasResult
    {
        return $this->httpConnector->post('/v3/creditCard/tokenizeCreditCard', CreditCardRequest::resolveData($data));
    }

    public function getPreAuthorizationConfig(): AsaasResult
    {
        return $this->httpConnector->get('/v3/creditCard/preAuthorization/config', []);
    }

    /** @param array<string, mixed>|PreAuthConfigRequest $data */
    public function setPreAuthorizationConfig(array|PreAuthConfigRequest $data): AsaasResult
    {
        return $this->httpConnector->post('/v3/creditCard/preAuthorization/config', PreAuthConfigRequest::resolveData($data));
    }
}
