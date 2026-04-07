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
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreateWebhookRequest $data */
    public function create(array|CreateWebhookRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/webhooks', CreateWebhookRequest::resolveData($data));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/webhooks', $query);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/webhooks/%s', IdGuard::validate($id)), []);
    }

    /** @param array<string, mixed>|UpdateWebhookRequest $data */
    public function update(string $id, array|UpdateWebhookRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('/v3/webhooks/%s', IdGuard::validate($id)), UpdateWebhookRequest::resolveData($data));
    }

    public function delete(string $id): AsaasResult
    {
        return $this->connector->delete(sprintf('/v3/webhooks/%s', IdGuard::validate($id)));
    }

    public function removeBackoff(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/webhooks/%s/removeBackoff', IdGuard::validate($id)), []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/webhooks', $filters);
    }
}
