<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\CreditCard\Request\SetPreAuthConfigRequest;
use OwnerPro\Asaas\CreditCard\Request\TokenizeCreditCardRequest;
use OwnerPro\Asaas\CreditCard\Response\CreditCardResponse;
use OwnerPro\Asaas\CreditCard\Response\PreAuthConfigResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;

final class CreditCardResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|TokenizeCreditCardRequest $data */
    public function tokenize(array|TokenizeCreditCardRequest $data): AsaasResult
    {
        $request = $data instanceof TokenizeCreditCardRequest ? $data : TokenizeCreditCardRequest::fromArray($data);

        return $this->asaasConnector->post('/v3/creditCard/tokenizeCreditCard', $request->toArray(), CreditCardResponse::class);
    }

    public function getPreAuthorizationConfig(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/creditCard/preAuthorization/config', [], PreAuthConfigResponse::class);
    }

    /** @param array<string, mixed>|SetPreAuthConfigRequest $data */
    public function setPreAuthorizationConfig(array|SetPreAuthConfigRequest $data): AsaasResult
    {
        $request = $data instanceof SetPreAuthConfigRequest ? $data : SetPreAuthConfigRequest::fromArray($data);

        return $this->asaasConnector->post('/v3/creditCard/preAuthorization/config', $request->toArray(), PreAuthConfigResponse::class);
    }
}
