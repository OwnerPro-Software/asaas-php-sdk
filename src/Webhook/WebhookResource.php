<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use Generator;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;

final readonly class WebhookResource
{
    private const string BASE = '/webhooks';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreateWebhookRequest $data */
    public function create(array|CreateWebhookRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE, CreateWebhookRequest::resolveData($data));
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

    /** @param array<string, mixed>|UpdateWebhookRequest $data */
    public function update(string $id, array|UpdateWebhookRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('%s/%s', self::BASE, IdGuard::validate($id)), UpdateWebhookRequest::resolveData($data));
    }

    public function delete(string $id): AsaasResult
    {
        return $this->connector->delete(sprintf('%s/%s', self::BASE, IdGuard::validate($id)));
    }

    public function removeBackoff(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/removeBackoff', self::BASE, IdGuard::validate($id)));
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
