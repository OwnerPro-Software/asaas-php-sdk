<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

interface Connector
{
    /** @param array<string, mixed> $query */
    public function get(string $path, array $query): AsaasResult;

    /** @param array<string, mixed> $data */
    public function post(string $path, array $data): AsaasResult;

    /** @param array<string, mixed> $data */
    public function put(string $path, array $data): AsaasResult;

    public function delete(string $path): AsaasResult;

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
