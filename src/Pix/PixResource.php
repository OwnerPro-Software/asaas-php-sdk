<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use Generator;
use OwnerPro\Asaas\Pix\Request\PixKeyRequest;
use OwnerPro\Asaas\Pix\Request\StaticQrCodeRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class PixResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|PixKeyRequest $data */
    public function createKey(array|PixKeyRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/pix/addressKeys', PixKeyRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function listKeys(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/pix/addressKeys', $query);
    }

    public function findKey(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/pix/addressKeys/%s', IdGuard::validate($id)), []);
    }

    public function deleteKey(string $id): AsaasResult
    {
        return $this->connector->delete(sprintf('/v3/pix/addressKeys/%s', IdGuard::validate($id)));
    }

    /** @param array<string, mixed>|StaticQrCodeRequest $data */
    public function createStaticQrCode(array|StaticQrCodeRequest $data = []): AsaasResult
    {
        return $this->connector->post('/v3/pix/qrCodes/static', StaticQrCodeRequest::resolveData($data));
    }

    public function deleteStaticQrCode(string $id): AsaasResult
    {
        return $this->connector->delete(sprintf('/v3/pix/qrCodes/static/%s', IdGuard::validate($id)));
    }

    public function tokenBucket(): AsaasResult
    {
        return $this->connector->get('/v3/pix/tokenBucket/addressKey', []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/pix/addressKeys', $filters);
    }
}
