<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\CreditCard\Request\CreditCardRequest;
use OwnerPro\Asaas\CreditCard\Request\PreAuthConfigRequest;
use OwnerPro\Asaas\CreditCard\Response\CreditCardResponse;
use OwnerPro\Asaas\CreditCard\Response\PreAuthConfigResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;

final readonly class CreditCardResource
{
    public function __construct(private AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreditCardRequest $data */
    public function tokenize(array|CreditCardRequest $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/creditCard/tokenizeCreditCard', CreditCardRequest::resolveData($data), CreditCardResponse::class);
    }

    public function getPreAuthorizationConfig(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/creditCard/preAuthorization/config', [], PreAuthConfigResponse::class);
    }

    /** @param array<string, mixed>|PreAuthConfigRequest $data */
    public function setPreAuthorizationConfig(array|PreAuthConfigRequest $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/creditCard/preAuthorization/config', PreAuthConfigRequest::resolveData($data), PreAuthConfigResponse::class);
    }
}
