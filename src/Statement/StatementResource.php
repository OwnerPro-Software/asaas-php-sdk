<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Statement;

use Generator;
use OwnerPro\Asaas\Statement\Response\StatementResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;

final readonly class StatementResource
{
    public function __construct(private AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/financialTransactions', $query, StatementResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, StatementResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/financialTransactions', $filters, StatementResponse::class);
    }
}
