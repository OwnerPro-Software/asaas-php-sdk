<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

use Generator;
use OwnerPro\Asaas\PixTransaction\Request\DecodeQrCodeRequest;
use OwnerPro\Asaas\PixTransaction\Request\PayQrCodeRequest;
use OwnerPro\Asaas\PixTransaction\Response\DecodedQrCodeResponse;
use OwnerPro\Asaas\PixTransaction\Response\PixTransactionResponse;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;

final readonly class PixTransactionResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|DecodeQrCodeRequest $data */
    public function decodeQrCode(array|DecodeQrCodeRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/pix/qrCodes/decode', DecodeQrCodeRequest::resolveData($data), DecodedQrCodeResponse::class);
    }

    /** @param array<string, mixed>|PayQrCodeRequest $data */
    public function payQrCode(array|PayQrCodeRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/pix/qrCodes/pay', PayQrCodeRequest::resolveData($data), PixTransactionResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/pix/transactions', $query, PixTransactionResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get('/v3/pix/transactions/'.$id, [], PixTransactionResponse::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/pix/transactions/%s/cancel', $id), [], PixTransactionResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PixTransactionResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/pix/transactions', $filters, PixTransactionResponse::class);
    }
}
