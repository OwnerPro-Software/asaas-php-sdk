<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedDTO;

final class WebhookResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreateWebhookData $data */
    public function create(array|CreateWebhookData $data): AsaasResult
    {
        $dto = $data instanceof CreateWebhookData ? $data : CreateWebhookData::fromArray($data);

        return $this->asaasConnector->post('/v3/webhooks', $dto->toArray(), WebhookDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/webhooks', $query, WebhookDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/webhooks/'.$id, [], WebhookDTO::class);
    }

    /** @param array<string, mixed>|UpdateWebhookData $data */
    public function update(string $id, array|UpdateWebhookData $data): AsaasResult
    {
        $dto = $data instanceof UpdateWebhookData ? $data : UpdateWebhookData::fromArray($data);

        return $this->asaasConnector->put('/v3/webhooks/'.$id, $dto->toArray(), WebhookDTO::class);
    }

    public function delete(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/webhooks/'.$id, DeletedDTO::class);
    }

    public function removeBackoff(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/webhooks/%s/removeBackoff', $id), [], RemoveBackoffDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, WebhookDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/webhooks', $filters, WebhookDTO::class);
    }
}
