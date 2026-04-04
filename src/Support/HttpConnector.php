<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

interface HttpConnector
{
    /** @param array<string, mixed> $query */
    public function get(string $path, array $query): AsaasResult;

    /** @param array<string, mixed> $data */
    public function post(string $path, array $data): AsaasResult;

    /** @param array<string, mixed> $data */
    public function put(string $path, array $data): AsaasResult;

    public function delete(string $path): AsaasResult;
}
