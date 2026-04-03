<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Webhook\WebhookEvent;
use OwnerPro\Asaas\Webhook\WebhookSendType;

final readonly class UpdateWebhookRequest
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
        public ?string $authToken = null,
        public ?array $events = null,
    ) {}

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
