<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook\Request;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class UpdateWebhookRequest
{
    use HasArrayFactory;

    /** @param list<string>|null $events */
    public function __construct(
        public ?string $url = null,
        public ?string $email = null,
        public ?string $name = null,
        public ?bool $enabled = null,
        public ?int $apiVersion = null,
        public ?string $sendType = null,
        public ?string $authToken = null,
        public ?array $events = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}
