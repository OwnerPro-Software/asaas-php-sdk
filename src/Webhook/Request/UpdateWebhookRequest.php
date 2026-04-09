<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use JsonSerializable;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Support\Missing;
use OwnerPro\Asaas\Webhook\WebhookEvent;
use OwnerPro\Asaas\Webhook\WebhookSendType;
use SensitiveParameter;

final readonly class UpdateWebhookRequest implements JsonSerializable
{
    use HasUpdatableArrayFactory;
    use MasksSensitiveData;

    /** @param list<WebhookEvent|string>|Missing|null $events */
    public function __construct(
        public string|Missing|null $url = Missing::Value,
        public string|Missing|null $email = Missing::Value,
        public string|Missing|null $name = Missing::Value,
        public bool|Missing|null $enabled = Missing::Value,
        public int|Missing|null $apiVersion = Missing::Value,
        public WebhookSendType|string|Missing|null $sendType = Missing::Value,
        #[SensitiveParameter]
        public string|Missing|null $authToken = Missing::Value,
        public array|Missing|null $events = Missing::Value,
    ) {}

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        /** @var array<string, mixed> $fields */
        $fields = array_filter(get_object_vars($this), fn (mixed $v): bool => ! $v instanceof Missing);

        if (array_key_exists('authToken', $fields)) {
            $fields['authToken'] = $this->authToken !== null ? '***' : null;
        }

        return $fields;
    }

    /** @param array{url?: string|null, email?: string|null, name?: string|null, enabled?: bool|null, apiVersion?: int|null, sendType?: WebhookSendType|string|null, authToken?: string|null, events?: list<WebhookEvent|string>|null} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            url: array_key_exists('url', $data) ? $data['url'] : Missing::Value,
            email: array_key_exists('email', $data) ? $data['email'] : Missing::Value,
            name: array_key_exists('name', $data) ? $data['name'] : Missing::Value,
            enabled: array_key_exists('enabled', $data) ? $data['enabled'] : Missing::Value,
            apiVersion: array_key_exists('apiVersion', $data) ? $data['apiVersion'] : Missing::Value,
            sendType: array_key_exists('sendType', $data) ? $data['sendType'] : Missing::Value,
            authToken: array_key_exists('authToken', $data) ? $data['authToken'] : Missing::Value,
            events: array_key_exists('events', $data) ? $data['events'] : Missing::Value,
        );
    }
}
