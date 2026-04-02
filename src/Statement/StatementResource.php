<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Statement;

use Generator;
use OwnerPro\Asaas\Statement\Response\StatementResponse;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\Connector;

final readonly class StatementResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/financialTransactions', $query, StatementResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, StatementResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/financialTransactions', $filters, StatementResponse::class);
    }
}
