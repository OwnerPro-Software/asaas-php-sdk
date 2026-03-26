<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateWebhookRequest
{
    use HasArrayFactory;

    /** @param list<string>|null $events */
    public function __construct(
        public readonly string $url,
        public readonly string $email,
        public readonly ?string $name = null,
        public readonly ?bool $enabled = null,
        public readonly ?int $apiVersion = null,
        public readonly ?string $sendType = null,
        public readonly ?string $authToken = null,
        public readonly ?array $events = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['url', 'email'];
    }
}
