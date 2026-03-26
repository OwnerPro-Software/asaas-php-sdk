<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedDTO;

final class AccountResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateAccountData $data */
    public function create(array|CreateAccountData $data): AsaasResult
    {
        $dto = $data instanceof CreateAccountData ? $data : CreateAccountData::fromArray($data);

        return $this->asaasConnector->post('/v3/accounts', $dto->toArray(), AccountDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/accounts', $query, AccountDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/accounts/'.$id, [], AccountDTO::class);
    }

    public function listAccessTokens(string $accountId): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/accounts/%s/accessTokens', $accountId), [], AccessTokenDTO::class);
    }

    public function createAccessToken(string $accountId): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/accounts/%s/accessTokens', $accountId), [], AccessTokenDTO::class);
    }

    /** @param array<string, mixed>|UpdateAccessTokenData $data */
    public function updateAccessToken(string $accountId, string $tokenId, array|UpdateAccessTokenData $data): AsaasResult
    {
        $dto = $data instanceof UpdateAccessTokenData ? $data : UpdateAccessTokenData::fromArray($data);

        return $this->asaasConnector->put(sprintf('/v3/accounts/%s/accessTokens/%s', $accountId, $tokenId), $dto->toArray(), AccessTokenDTO::class);
    }

    public function deleteAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->asaasConnector->delete(sprintf('/v3/accounts/%s/accessTokens/%s', $accountId, $tokenId), DeletedDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, AccountDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/accounts', $filters, AccountDTO::class);
    }
}
