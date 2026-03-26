<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

use Generator;
use OwnerPro\Asaas\PixTransaction\Response\DecodedQrCodeResponse;
use OwnerPro\Asaas\PixTransaction\Response\PixTransactionResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final class PixTransactionResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed> $data */
    public function decodeQrCode(array $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/pix/qrCodes/decode', $data, DecodedQrCodeResponse::class);
    }

    /** @param array<string, mixed> $data */
    public function payQrCode(array $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/pix/qrCodes/pay', $data, PixTransactionResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/pix/transactions', $query, PixTransactionResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/pix/transactions/'.$id, [], PixTransactionResponse::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/pix/transactions/%s/cancel', $id), [], PixTransactionResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PixTransactionResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/pix/transactions', $filters, PixTransactionResponse::class);
    }
}
