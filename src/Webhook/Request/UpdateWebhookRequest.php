<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use JsonSerializable;
use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Support\Missing;
use OwnerPro\Asaas\Support\Redactable;
use OwnerPro\Asaas\Webhook\WebhookEvent;
use OwnerPro\Asaas\Webhook\WebhookSendType;
use SensitiveParameter;

final readonly class UpdateWebhookRequest implements JsonSerializable, Redactable
{
    use HasUpdatableArrayFactory;
    use MasksSensitiveData;

    /** @param list<WebhookEvent|string>|Missing $events */
    public function __construct(
        public string|Missing $url = Missing::Value,
        public string|Missing $name = Missing::Value,
        public bool|Missing $enabled = Missing::Value,
        public bool|Missing $interrupted = Missing::Value,
        public WebhookSendType|string|Missing $sendType = Missing::Value,
        #[SensitiveParameter]
        public string|Missing $authToken = Missing::Value,
        public array|Missing $events = Missing::Value,
    ) {}

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        /** @var array<string, mixed> $fields */
        $fields = array_filter(get_object_vars($this), fn (mixed $v): bool => ! $v instanceof Missing);

        if (array_key_exists('authToken', $fields)) {
            $fields['authToken'] = '***';
        }

        return $fields;
    }

    /** @param array{url?: string, name?: string, enabled?: bool, interrupted?: bool, sendType?: WebhookSendType|string, authToken?: string, events?: list<WebhookEvent|string>} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            url: $data['url'] ?? Missing::Value,
            name: $data['name'] ?? Missing::Value,
            enabled: $data['enabled'] ?? Missing::Value,
            interrupted: $data['interrupted'] ?? Missing::Value,
            sendType: $data['sendType'] ?? Missing::Value,
            authToken: $data['authToken'] ?? Missing::Value,
            events: $data['events'] ?? Missing::Value,
        );
    }
}
