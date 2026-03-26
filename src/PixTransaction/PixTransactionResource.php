<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final class PixTransactionResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed> $data */
    public function decodeQrCode(array $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/pix/qrCodes/decode', $data, DecodedQrCodeDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function payQrCode(array $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/pix/qrCodes/pay', $data, PixTransactionDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/pix/transactions', $query, PixTransactionDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/pix/transactions/'.$id, [], PixTransactionDTO::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/pix/transactions/%s/cancel', $id), [], PixTransactionDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PixTransactionDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/pix/transactions', $filters, PixTransactionDTO::class);
    }
}
