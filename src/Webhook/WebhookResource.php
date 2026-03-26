<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedResponse;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;
use OwnerPro\Asaas\Webhook\Response\RemoveBackoffResponse;
use OwnerPro\Asaas\Webhook\Response\WebhookResponse;

final class WebhookResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateWebhookRequest $data */
    public function create(array|CreateWebhookRequest $data): AsaasResult
    {
        $request = $data instanceof CreateWebhookRequest ? $data : CreateWebhookRequest::fromArray($data);

        return $this->asaasConnector->post('/v3/webhooks', $request->toArray(), WebhookResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/webhooks', $query, WebhookResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/webhooks/'.$id, [], WebhookResponse::class);
    }

    /** @param array<string, mixed>|UpdateWebhookRequest $data */
    public function update(string $id, array|UpdateWebhookRequest $data): AsaasResult
    {
        $request = $data instanceof UpdateWebhookRequest ? $data : UpdateWebhookRequest::fromArray($data);

        return $this->asaasConnector->put('/v3/webhooks/'.$id, $request->toArray(), WebhookResponse::class);
    }

    public function delete(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/webhooks/'.$id, DeletedResponse::class);
    }

    public function removeBackoff(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/webhooks/%s/removeBackoff', $id), [], RemoveBackoffResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, WebhookResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/webhooks', $filters, WebhookResponse::class);
    }
}
