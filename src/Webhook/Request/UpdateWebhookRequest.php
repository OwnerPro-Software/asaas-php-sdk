<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use JsonSerializable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Webhook\WebhookEvent;
use OwnerPro\Asaas\Webhook\WebhookSendType;
use SensitiveParameter;

final readonly class UpdateWebhookRequest implements JsonSerializable
{
    use HasArrayFactory;

    /** @param list<WebhookEvent|string>|null $events */
    public function __construct(
        public ?string $url = null,
        public ?string $email = null,
        public ?string $name = null,
        public ?bool $enabled = null,
        public ?int $apiVersion = null,
        public WebhookSendType|string|null $sendType = null,
        #[SensitiveParameter]
        public ?string $authToken = null,
        public ?array $events = null,
    ) {}

    /** @return array{url: ?string, email: ?string, name: ?string, enabled: ?bool, apiVersion: ?int, sendType: WebhookSendType|string|null, authToken: ?string, events: ?list<WebhookEvent|string>} */
    public function __debugInfo(): array
    {
        return [
            'url' => $this->url,
            'email' => $this->email,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'apiVersion' => $this->apiVersion,
            'sendType' => $this->sendType,
            'authToken' => $this->authToken !== null ? '***' : null,
            'events' => $this->events,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->__debugInfo();
    }

    /** @param array{url?: string, email?: string, name?: string, enabled?: bool, apiVersion?: int, sendType?: WebhookSendType|string, authToken?: string, events?: list<WebhookEvent|string>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            url: $data['url'] ?? null,
            email: $data['email'] ?? null,
            name: $data['name'] ?? null,
            enabled: $data['enabled'] ?? null,
            apiVersion: $data['apiVersion'] ?? null,
            sendType: $data['sendType'] ?? null,
            authToken: $data['authToken'] ?? null,
            events: $data['events'] ?? null,
        );
    }
}
