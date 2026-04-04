<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

interface Connector extends HttpConnector
{
    /** @param array<string, mixed> $query */
    public function paginate(string $path, array $query): AsaasPaginatedResult;

    /**
     * Lazy iterator that auto-paginates through all pages.
     *
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(string $path, array $filters): Generator;
}
