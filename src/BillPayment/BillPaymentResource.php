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
use OwnerPro\Asaas\Support\IdGuard;

final readonly class BillPaymentResource
{
    private const string BASE = '/bill';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreateBillPaymentRequest $data */
    public function create(array|CreateBillPaymentRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE, CreateBillPaymentRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE, $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id));
    }

    /** @param array<string, mixed>|SimulateBillPaymentRequest $data */
    public function simulate(array|SimulateBillPaymentRequest $data = []): AsaasResult
    {
        return $this->connector->post(self::BASE.'/simulate', SimulateBillPaymentRequest::resolveData($data));
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/cancel'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::BASE, $filters);
    }

    private function path(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::BASE, IdGuard::validate($id), $suffix);
    }
}
