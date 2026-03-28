<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

use Generator;
use OwnerPro\Asaas\BillPayment\Request\CreateBillPaymentRequest;
use OwnerPro\Asaas\BillPayment\Request\SimulateBillPaymentRequest;
use OwnerPro\Asaas\BillPayment\Response\BillPaymentResponse;
use OwnerPro\Asaas\BillPayment\Response\BillSimulationResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final readonly class BillPaymentResource
{
    public function __construct(private AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateBillPaymentRequest $data */
    public function create(array|CreateBillPaymentRequest $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/bill', CreateBillPaymentRequest::resolveData($data), BillPaymentResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/bill', $query, BillPaymentResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/bill/'.$id, [], BillPaymentResponse::class);
    }

    /** @param array<string, mixed>|SimulateBillPaymentRequest $data */
    public function simulate(array|SimulateBillPaymentRequest $data = []): AsaasResult
    {
        return $this->asaasConnector->post('/v3/bill/simulate', SimulateBillPaymentRequest::resolveData($data), BillSimulationResponse::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/bill/%s/cancel', $id), [], BillPaymentResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, BillPaymentResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/bill', $filters, BillPaymentResponse::class);
    }
}
