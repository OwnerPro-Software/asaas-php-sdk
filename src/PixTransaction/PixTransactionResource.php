<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

use Generator;
use OwnerPro\Asaas\PixTransaction\Request\DecodeQrCodeRequest;
use OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class PixTransactionResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|DecodeQrCodeRequest $data */
    public function decodeQrCode(array|DecodeQrCodeRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/pix/qrCodes/decode', DecodeQrCodeRequest::resolveData($data));
    }

    /** @param array<string, mixed>|PayQrCodeRequest $data */
    public function payQrCode(array|PayQrCodeRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/pix/qrCodes/pay', PayQrCodeRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/pix/transactions', $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/pix/transactions/%s', IdGuard::validate($id)), []);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/pix/transactions/%s/cancel', IdGuard::validate($id)), []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/pix/transactions', $filters);
    }
}
