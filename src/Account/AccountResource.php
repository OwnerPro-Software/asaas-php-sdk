<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use Generator;
use OwnerPro\Asaas\Account\Request\AccessTokenRequest;
use OwnerPro\Asaas\Account\Request\AccountRequest;
use OwnerPro\Asaas\Account\Response\AccessTokenResponse;
use OwnerPro\Asaas\Account\Response\AccountResponse;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\DeletedResponse;

final readonly class AccountResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|AccountRequest $data */
    public function create(array|AccountRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/accounts', AccountRequest::resolveData($data), AccountResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/accounts', $query, AccountResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get('/v3/accounts/'.$id, [], AccountResponse::class);
    }

    public function listAccessTokens(string $accountId): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/accounts/%s/accessTokens', $accountId), [], AccessTokenResponse::class);
    }

    public function createAccessToken(string $accountId): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/accounts/%s/accessTokens', $accountId), [], AccessTokenResponse::class);
    }

    /** @param array<string, mixed>|AccessTokenRequest $data */
    public function updateAccessToken(string $accountId, string $tokenId, array|AccessTokenRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('/v3/accounts/%s/accessTokens/%s', $accountId, $tokenId), AccessTokenRequest::resolveData($data), AccessTokenResponse::class);
    }

    public function deleteAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->connector->delete(sprintf('/v3/accounts/%s/accessTokens/%s', $accountId, $tokenId), DeletedResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, AccountResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/accounts', $filters, AccountResponse::class);
    }
}
