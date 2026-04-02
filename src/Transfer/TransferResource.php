<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

use Generator;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Transfer\Request\TransferRequest;
use OwnerPro\Asaas\Transfer\Response\TransferResponse;

final readonly class TransferResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|TransferRequest $data */
    public function create(array|TransferRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/transfers', TransferRequest::resolveData($data), TransferResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/transfers', $query, TransferResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get('/v3/transfers/'.$id, [], TransferResponse::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/transfers/%s/cancel', $id), [], TransferResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, TransferResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/transfers', $filters, TransferResponse::class);
    }
}
