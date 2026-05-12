<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Statement;

use Generator;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;

final readonly class StatementResource
{
    private const string BASE = '/financialTransactions';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE, $query);
    }

    public function balance(): AsaasResult
    {
        return $this->connector->get('/finance/balance');
    }

    /** @param array<string, mixed> $query */
    public function paymentStatistics(array $query = []): AsaasResult
    {
        return $this->connector->get('/finance/payment/statistics', $query);
    }

    /** @param array<string, mixed> $query */
    public function splitStatistics(array $query = []): AsaasResult
    {
        return $this->connector->get('/finance/split/statistics', $query);
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
