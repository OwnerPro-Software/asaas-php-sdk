<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Statement;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;

final class StatementResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/financialTransactions', $query, StatementDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, StatementDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/financialTransactions', $filters, StatementDTO::class);
    }
}
