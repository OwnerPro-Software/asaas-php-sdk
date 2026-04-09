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
    private const string BASE = '/accounts';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|AccountRequest $data */
    public function create(array|AccountRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE, AccountRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE, $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s', self::BASE, IdGuard::validate($id)), []);
    }

    public function listAccessTokens(string $accountId): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s/accessTokens', self::BASE, IdGuard::validate($accountId)), []);
    }

    public function createAccessToken(string $accountId): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/accessTokens', self::BASE, IdGuard::validate($accountId)), []);
    }

    /** @param array<string, mixed>|AccessTokenRequest $data */
    public function updateAccessToken(string $accountId, string $tokenId, array|AccessTokenRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('%s/%s/accessTokens/%s', self::BASE, IdGuard::validate($accountId), IdGuard::validate($tokenId)), AccessTokenRequest::resolveData($data));
    }

    public function deleteAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->connector->delete(sprintf('%s/%s/accessTokens/%s', self::BASE, IdGuard::validate($accountId), IdGuard::validate($tokenId)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::BASE, $filters);
    }
}
