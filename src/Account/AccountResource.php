<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use Generator;
use OwnerPro\Asaas\Account\Request\AccessTokenRequest;
use OwnerPro\Asaas\Account\Request\AccountRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class AccountResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|AccountRequest $data */
    public function create(array|AccountRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/accounts', AccountRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/accounts', $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/accounts/%s', IdGuard::validate($id)), []);
    }

    public function listAccessTokens(string $accountId): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/accounts/%s/accessTokens', IdGuard::validate($accountId)), []);
    }

    public function createAccessToken(string $accountId): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/accounts/%s/accessTokens', IdGuard::validate($accountId)), []);
    }

    /** @param array<string, mixed>|AccessTokenRequest $data */
    public function updateAccessToken(string $accountId, string $tokenId, array|AccessTokenRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('/v3/accounts/%s/accessTokens/%s', IdGuard::validate($accountId), IdGuard::validate($tokenId)), AccessTokenRequest::resolveData($data));
    }

    public function deleteAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->connector->delete(sprintf('/v3/accounts/%s/accessTokens/%s', IdGuard::validate($accountId), IdGuard::validate($tokenId)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/accounts', $filters);
    }
}
