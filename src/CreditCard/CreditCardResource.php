<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\CreditCard;

use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;

final class CreditCardResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|TokenizeCreditCardData $data */
    public function tokenize(array|TokenizeCreditCardData $data): AsaasResult
    {
        $dto = $data instanceof TokenizeCreditCardData ? $data : TokenizeCreditCardData::fromArray($data);

        return $this->asaasConnector->post('/v3/creditCard/tokenizeCreditCard', $dto->toArray(), CreditCardDTO::class);
    }

    public function getPreAuthorizationConfig(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/creditCard/preAuthorization/config', [], PreAuthConfigDTO::class);
    }

    /** @param array<string, mixed>|SetPreAuthConfigData $data */
    public function setPreAuthorizationConfig(array|SetPreAuthConfigData $data): AsaasResult
    {
        $dto = $data instanceof SetPreAuthConfigData ? $data : SetPreAuthConfigData::fromArray($data);

        return $this->asaasConnector->post('/v3/creditCard/preAuthorization/config', $dto->toArray(), PreAuthConfigDTO::class);
    }
}
