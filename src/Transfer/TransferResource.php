<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final class TransferResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateTransferData $data */
    public function create(array|CreateTransferData $data): AsaasResult
    {
        $dto = $data instanceof CreateTransferData ? $data : CreateTransferData::fromArray($data);

        return $this->asaasConnector->post('/v3/transfers', $dto->toArray(), TransferDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/transfers', $query, TransferDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/transfers/'.$id, [], TransferDTO::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->delete(sprintf('/v3/transfers/%s/cancel', $id), TransferDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, TransferDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/transfers', $filters, TransferDTO::class);
    }
}
