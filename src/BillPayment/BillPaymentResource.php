<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

use Generator;
use OwnerPro\Asaas\BillPayment\Request\CreateBillPaymentRequest;
use OwnerPro\Asaas\BillPayment\Request\SimulateBillPaymentRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;

final readonly class BillPaymentResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreateBillPaymentRequest $data */
    public function create(array|CreateBillPaymentRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/bill', CreateBillPaymentRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/bill', $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get('/v3/bill/'.$id, []);
    }

    /** @param array<string, mixed>|SimulateBillPaymentRequest $data */
    public function simulate(array|SimulateBillPaymentRequest $data = []): AsaasResult
    {
        return $this->connector->post('/v3/bill/simulate', SimulateBillPaymentRequest::resolveData($data));
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/bill/%s/cancel', $id), []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/bill', $filters);
    }
}
