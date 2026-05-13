<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use Generator;
use OwnerPro\Asaas\Account\Request\AccountRequest;
use OwnerPro\Asaas\Account\Request\CreateAccessTokenRequest;
use OwnerPro\Asaas\Account\Request\EscrowConfigRequest;
use OwnerPro\Asaas\Account\Request\UpdateAccessTokenRequest;
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
        return $this->connector->get($this->path($id));
    }

    public function listAccessTokens(string $accountId): AsaasResult
    {
        return $this->connector->get($this->path($accountId, '/accessTokens'));
    }

    /**
     * `GET /v3/accounts/{id}/accessTokens/{accessTokenId}`.
     *
     * The OpenAPI spec only documents the list variant
     * (`GET /v3/accounts/{id}/accessTokens`); the single-token retrieve is not
     * formally specced but Asaas accepts it in practice. Kept as a convenience
     * for callers that need to fetch one specific token without paginating.
     */
    public function findAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->connector->get($this->tokenPath($accountId, $tokenId));
    }

    /** @param array<string, mixed>|CreateAccessTokenRequest|null $data */
    public function createAccessToken(string $accountId, array|CreateAccessTokenRequest|null $data = null): AsaasResult
    {
        $body = $data === null ? [] : CreateAccessTokenRequest::resolveData($data);

        return $this->connector->post($this->path($accountId, '/accessTokens'), $body);
    }

    /** @param array<string, mixed>|UpdateAccessTokenRequest $data */
    public function updateAccessToken(string $accountId, string $tokenId, array|UpdateAccessTokenRequest $data): AsaasResult
    {
        return $this->connector->put($this->tokenPath($accountId, $tokenId), UpdateAccessTokenRequest::resolveData($data));
    }

    public function deleteAccessToken(string $accountId, string $tokenId): AsaasResult
    {
        return $this->connector->delete($this->tokenPath($accountId, $tokenId));
    }

    /**
     * Escrow-config management is exposed on `AccountResource` for ergonomics
     * (subaccount lifecycle stays in one place). The endpoints actually live
     * under the `escrow` spec domain; a dedicated `EscrowResource` may
     * supersede these methods in a future major release.
     */
    public function escrowConfig(string $accountId): AsaasResult
    {
        return $this->connector->get($this->path($accountId, '/escrow'));
    }

    /** @param array<string, mixed>|EscrowConfigRequest $data */
    public function setEscrowConfig(string $accountId, array|EscrowConfigRequest $data): AsaasResult
    {
        return $this->connector->post($this->path($accountId, '/escrow'), EscrowConfigRequest::resolveData($data));
    }

    public function defaultEscrowConfig(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/escrow');
    }

    /** @param array<string, mixed>|EscrowConfigRequest $data */
    public function setDefaultEscrowConfig(array|EscrowConfigRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE.'/escrow', EscrowConfigRequest::resolveData($data));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::BASE, $filters);
    }

    private function path(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::BASE, IdGuard::validate($id), $suffix);
    }

    private function tokenPath(string $accountId, string $tokenId): string
    {
        return sprintf('%s/%s/accessTokens/%s', self::BASE, IdGuard::validate($accountId), IdGuard::validate($tokenId));
    }
}
