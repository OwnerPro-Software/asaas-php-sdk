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
    private const string KEYS = '/pix/addressKeys';

    private const string STATIC_QR = '/pix/qrCodes/static';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|PixKeyRequest $data */
    public function createKey(array|PixKeyRequest $data): AsaasResult
    {
        return $this->connector->post(self::KEYS, PixKeyRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function listKeys(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::KEYS, $query);
    }

    public function findKey(string $id): AsaasResult
    {
        return $this->connector->get($this->path(self::KEYS, $id));
    }

    public function deleteKey(string $id): AsaasResult
    {
        return $this->connector->delete($this->path(self::KEYS, $id));
    }

    /** @param array<string, mixed>|StaticQrCodeRequest $data */
    public function createStaticQrCode(array|StaticQrCodeRequest $data = []): AsaasResult
    {
        return $this->connector->post(self::STATIC_QR, StaticQrCodeRequest::resolveData($data));
    }

    public function deleteStaticQrCode(string $id): AsaasResult
    {
        return $this->connector->delete($this->path(self::STATIC_QR, $id));
    }

    public function tokenBucket(): AsaasResult
    {
        return $this->connector->get('/pix/tokenBucket/addressKey');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::KEYS, $filters);
    }

    private function path(string $base, string $id): string
    {
        return sprintf('%s/%s', $base, IdGuard::validate($id));
    }
}
