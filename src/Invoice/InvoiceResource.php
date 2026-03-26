<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final class InvoiceResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateInvoiceData $data */
    public function create(array|CreateInvoiceData $data): AsaasResult
    {
        $dto = $data instanceof CreateInvoiceData ? $data : CreateInvoiceData::fromArray($data);

        return $this->asaasConnector->post('/v3/invoices', $dto->toArray(), InvoiceDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/invoices', $query, InvoiceDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/invoices/'.$id, [], InvoiceDTO::class);
    }

    /** @param array<string, mixed>|UpdateInvoiceData $data */
    public function update(string $id, array|UpdateInvoiceData $data): AsaasResult
    {
        $dto = $data instanceof UpdateInvoiceData ? $data : UpdateInvoiceData::fromArray($data);

        return $this->asaasConnector->put('/v3/invoices/'.$id, $dto->toArray(), InvoiceDTO::class);
    }

    public function authorize(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/invoices/%s/authorize', $id), [], InvoiceDTO::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/invoices/%s/cancel', $id), [], InvoiceDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, InvoiceDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/invoices', $filters, InvoiceDTO::class);
    }
}
