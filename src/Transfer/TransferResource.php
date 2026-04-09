<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

use Generator;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;
use OwnerPro\Asaas\Transfer\Request\TransferRequest;

final readonly class TransferResource
{
    private const string BASE = '/transfers';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|TransferRequest $data */
    public function create(array|TransferRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE, TransferRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE, $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s', self::BASE, IdGuard::validate($id)));
    }

    public function cancel(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/cancel', self::BASE, IdGuard::validate($id)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::BASE, $filters);
    }
}
