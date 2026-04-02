<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use Generator;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\DeletedResponse;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;
use OwnerPro\Asaas\Webhook\Response\RemoveBackoffResponse;
use OwnerPro\Asaas\Webhook\Response\WebhookResponse;

final readonly class WebhookResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreateWebhookRequest $data */
    public function create(array|CreateWebhookRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/webhooks', CreateWebhookRequest::resolveData($data), WebhookResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/webhooks', $query, WebhookResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get('/v3/webhooks/'.$id, [], WebhookResponse::class);
    }

    /** @param array<string, mixed>|UpdateWebhookRequest $data */
    public function update(string $id, array|UpdateWebhookRequest $data): AsaasResult
    {
        return $this->connector->put('/v3/webhooks/'.$id, UpdateWebhookRequest::resolveData($data), WebhookResponse::class);
    }

    public function delete(string $id): AsaasResult
    {
        return $this->connector->delete('/v3/webhooks/'.$id, DeletedResponse::class);
    }

    public function removeBackoff(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/webhooks/%s/removeBackoff', $id), [], RemoveBackoffResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, WebhookResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/webhooks', $filters, WebhookResponse::class);
    }
}
