<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;

final class BillPaymentResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateBillPaymentData $data */
    public function create(array|CreateBillPaymentData $data): AsaasResult
    {
        $dto = $data instanceof CreateBillPaymentData ? $data : CreateBillPaymentData::fromArray($data);

        return $this->asaasConnector->post('/v3/bill', $dto->toArray(), BillPaymentDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/bill', $query, BillPaymentDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/bill/'.$id, [], BillPaymentDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function simulate(array $data = []): AsaasResult
    {
        return $this->asaasConnector->post('/v3/bill/simulate', $data, BillSimulationDTO::class);
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/bill/%s/cancel', $id), [], BillPaymentDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, BillPaymentDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/bill', $filters, BillPaymentDTO::class);
    }
}
