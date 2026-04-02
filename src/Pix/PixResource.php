<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use Generator;
use OwnerPro\Asaas\Pix\Request\PixKeyRequest;
use OwnerPro\Asaas\Pix\Request\StaticQrCodeRequest;
use OwnerPro\Asaas\Pix\Response\PixResponse;
use OwnerPro\Asaas\Pix\Response\StaticQrCodeResponse;
use OwnerPro\Asaas\Pix\Response\TokenBucketResponse;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\DeletedResponse;

final readonly class PixResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|PixKeyRequest $data */
    public function createKey(array|PixKeyRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/pix/addressKeys', PixKeyRequest::resolveData($data), PixResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function listKeys(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/pix/addressKeys', $query, PixResponse::class);
    }

    public function findKey(string $id): AsaasResult
    {
        return $this->connector->get('/v3/pix/addressKeys/'.$id, [], PixResponse::class);
    }

    public function deleteKey(string $id): AsaasResult
    {
        return $this->connector->delete('/v3/pix/addressKeys/'.$id, PixResponse::class);
    }

    /** @param array<string, mixed>|StaticQrCodeRequest $data */
    public function createStaticQrCode(array|StaticQrCodeRequest $data = []): AsaasResult
    {
        return $this->connector->post('/v3/pix/qrCodes/static', StaticQrCodeRequest::resolveData($data), StaticQrCodeResponse::class);
    }

    public function deleteStaticQrCode(string $id): AsaasResult
    {
        return $this->connector->delete('/v3/pix/qrCodes/static/'.$id, DeletedResponse::class);
    }

    public function tokenBucket(): AsaasResult
    {
        return $this->connector->get('/v3/pix/tokenBucket/addressKey', [], TokenBucketResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PixResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/pix/addressKeys', $filters, PixResponse::class);
    }
}
