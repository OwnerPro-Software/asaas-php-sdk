<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice;

use Generator;
use OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest;
use OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class InvoiceResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreateInvoiceRequest $data */
    public function create(array|CreateInvoiceRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/invoices', CreateInvoiceRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/invoices', $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/invoices/%s', IdGuard::validate($id)), []);
    }

    /** @param array<string, mixed>|UpdateInvoiceRequest $data */
    public function update(string $id, array|UpdateInvoiceRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('/v3/invoices/%s', IdGuard::validate($id)), UpdateInvoiceRequest::resolveData($data));
    }

    public function authorize(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/invoices/%s/authorize', IdGuard::validate($id)), []);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/invoices/%s/cancel', IdGuard::validate($id)), []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/invoices', $filters);
    }
}
