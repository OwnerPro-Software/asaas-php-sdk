<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Webhook\WebhookEvent;
use OwnerPro\Asaas\Webhook\WebhookSendType;
use SensitiveParameter;

/**
 * Request body for `POST /v3/webhooks`.
 *
 * The OpenAPI spec does not mark any field as required for create, but this DTO
 * promotes `url` and `email` to mandatory positional arguments. Rationale:
 *  - A webhook without `url` is useless — Asaas has nowhere to deliver events.
 *  - Asaas notifies failure-backoff state through `email`; omitting it hides
 *    delivery issues from the operator.
 *
 * This is intentionally stricter than the spec to surface mistakes at
 * construction time rather than after the first failed delivery.
 */
final readonly class CreateWebhookRequest implements Arrayable, JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    /** @param list<WebhookEvent|string>|null $events */
    public function __construct(
        public string $url,
        public string $email,
        public ?string $name = null,
        public ?bool $enabled = null,
        public bool $interrupted = false,
        public ?int $apiVersion = null,
        public WebhookSendType|string|null $sendType = null,
        #[SensitiveParameter]
        public ?string $authToken = null,
        public ?array $events = null,
    ) {}

    /** @return array{url: string, email: string, name: ?string, enabled: ?bool, interrupted: bool, apiVersion: ?int, sendType: WebhookSendType|string|null, authToken: ?string, events: ?list<WebhookEvent|string>} */
    public function __debugInfo(): array
    {
        return [
            'url' => $this->url,
            'email' => $this->email,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'interrupted' => $this->interrupted,
            'apiVersion' => $this->apiVersion,
            'sendType' => $this->sendType,
            'authToken' => $this->authToken !== null ? '***' : null,
            'events' => $this->events,
        ];
    }

    /** @param array{url?: string, email?: string, name?: string, enabled?: bool, interrupted?: bool, apiVersion?: int, sendType?: WebhookSendType|string, authToken?: string, events?: list<WebhookEvent|string>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            url: $data['url'] ?? throw new InvalidArgumentException('CreateWebhookRequest: url is required'),
            email: $data['email'] ?? throw new InvalidArgumentException('CreateWebhookRequest: email is required'),
            name: $data['name'] ?? null,
            enabled: $data['enabled'] ?? null,
            interrupted: $data['interrupted'] ?? false,
            apiVersion: $data['apiVersion'] ?? null,
            sendType: $data['sendType'] ?? null,
            authToken: $data['authToken'] ?? null,
            events: $data['events'] ?? null,
        );
    }
}
