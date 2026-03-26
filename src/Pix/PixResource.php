<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Pix;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedDTO;

final class PixResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreatePixKeyData $data */
    public function createKey(array|CreatePixKeyData $data): AsaasResult
    {
        $dto = $data instanceof CreatePixKeyData ? $data : CreatePixKeyData::fromArray($data);

        return $this->asaasConnector->post('/v3/pix/addressKeys', $dto->toArray(), PixDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function listKeys(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/pix/addressKeys', $query, PixDTO::class);
    }

    public function findKey(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/pix/addressKeys/'.$id, [], PixDTO::class);
    }

    public function deleteKey(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/pix/addressKeys/'.$id, PixDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function createStaticQrCode(array $data = []): AsaasResult
    {
        return $this->asaasConnector->post('/v3/pix/qrCodes/static', $data, StaticQrCodeDTO::class);
    }

    public function deleteStaticQrCode(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/pix/qrCodes/static/'.$id, DeletedDTO::class);
    }

    public function tokenBucket(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/pix/tokenBucket/addressKey', [], TokenBucketDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PixDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/pix/addressKeys', $filters, PixDTO::class);
    }
}
