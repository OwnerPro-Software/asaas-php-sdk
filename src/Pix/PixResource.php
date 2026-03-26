<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use Generator;
use OwnerPro\Asaas\Pix\Request\CreatePixKeyRequest;
use OwnerPro\Asaas\Pix\Request\CreateStaticQrCodeRequest;
use OwnerPro\Asaas\Pix\Response\PixResponse;
use OwnerPro\Asaas\Pix\Response\StaticQrCodeResponse;
use OwnerPro\Asaas\Pix\Response\TokenBucketResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedResponse;

final class PixResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreatePixKeyRequest $data */
    public function createKey(array|CreatePixKeyRequest $data): AsaasResult
    {
        $request = $data instanceof CreatePixKeyRequest ? $data : CreatePixKeyRequest::fromArray($data);

        return $this->asaasConnector->post('/v3/pix/addressKeys', $request->toArray(), PixResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function listKeys(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/pix/addressKeys', $query, PixResponse::class);
    }

    public function findKey(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/pix/addressKeys/'.$id, [], PixResponse::class);
    }

    public function deleteKey(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/pix/addressKeys/'.$id, PixResponse::class);
    }

    /** @param array<string, mixed>|CreateStaticQrCodeRequest $data */
    public function createStaticQrCode(array|CreateStaticQrCodeRequest $data = []): AsaasResult
    {
        $request = $data instanceof CreateStaticQrCodeRequest ? $data : CreateStaticQrCodeRequest::fromArray($data);

        return $this->asaasConnector->post('/v3/pix/qrCodes/static', $request->toArray(), StaticQrCodeResponse::class);
    }

    public function deleteStaticQrCode(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/pix/qrCodes/static/'.$id, DeletedResponse::class);
    }

    public function tokenBucket(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/pix/tokenBucket/addressKey', [], TokenBucketResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PixResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/pix/addressKeys', $filters, PixResponse::class);
    }
}
