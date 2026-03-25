<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

final class AsaasClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $environment,
        private readonly int $timeout,
    ) {}

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }
}
