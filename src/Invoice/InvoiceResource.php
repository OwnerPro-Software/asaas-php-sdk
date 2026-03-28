<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice;

use Generator;
use OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest;
use OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest;
use OwnerPro\Asaas\Invoice\Response\InvoiceResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final readonly class InvoiceResource
{
    public function __construct(private AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateInvoiceRequest $data */
    public function create(array|CreateInvoiceRequest $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/invoices', CreateInvoiceRequest::resolveData($data), InvoiceResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/invoices', $query, InvoiceResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/invoices/'.$id, [], InvoiceResponse::class);
    }

    /** @param array<string, mixed>|UpdateInvoiceRequest $data */
    public function update(string $id, array|UpdateInvoiceRequest $data): AsaasResult
    {
        return $this->asaasConnector->put('/v3/invoices/'.$id, UpdateInvoiceRequest::resolveData($data), InvoiceResponse::class);
    }

    public function authorize(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/invoices/%s/authorize', $id), [], InvoiceResponse::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/invoices/%s/cancel', $id), [], InvoiceResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, InvoiceResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/invoices', $filters, InvoiceResponse::class);
    }
}
