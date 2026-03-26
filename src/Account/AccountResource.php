<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use Generator;
use OwnerPro\Asaas\Account\Request\CreateAccountRequest;
use OwnerPro\Asaas\Account\Request\UpdateAccessTokenRequest;
use OwnerPro\Asaas\Account\Response\AccessTokenResponse;
use OwnerPro\Asaas\Account\Response\AccountResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedResponse;

final class AccountResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateAccountRequest $data */
    public function create(array|CreateAccountRequest $data): AsaasResult
    {
        $request = $data instanceof CreateAccountRequest ? $data : CreateAccountRequest::fromArray($data);

        return $this->asaasConnector->post('/v3/accounts', $request->toArray(), AccountResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/accounts', $query, AccountResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/accounts/'.$id, [], AccountResponse::class);
    }

    public function listAccessTokens(string $accountId): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/accounts/%s/accessTokens', $accountId), [], AccessTokenResponse::class);
    }

    public function createAccessToken(string $accountId): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/accounts/%s/accessTokens', $accountId), [], AccessTokenResponse::class);
    }

    /** @param array<string, mixed>|UpdateAccessTokenRequest $data */
    public function updateAccessToken(string $accountId, string $tokenId, array|UpdateAccessTokenRequest $data): AsaasResult
    {
        $request = $data instanceof UpdateAccessTokenRequest ? $data : UpdateAccessTokenRequest::fromArray($data);

        return $this->asaasConnector->put(sprintf('/v3/accounts/%s/accessTokens/%s', $accountId, $tokenId), $request->toArray(), AccessTokenResponse::class);
    }

    public function deleteAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->asaasConnector->delete(sprintf('/v3/accounts/%s/accessTokens/%s', $accountId, $tokenId), DeletedResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, AccountResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/accounts', $filters, AccountResponse::class);
    }
}
