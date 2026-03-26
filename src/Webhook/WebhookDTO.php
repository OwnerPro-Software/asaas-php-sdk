<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use OwnerPro\Asaas\Support\BaseDTO;

final class WebhookDTO extends BaseDTO
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $url = null;

    public ?string $email = null;

    public ?bool $enabled = null;

    public ?bool $interrupted = null;

    public ?int $apiVersion = null;

    public ?bool $hasAuthToken = null;

    public ?string $sendType = null;

    public ?int $penalizedRequestsCount = null;

    /** @var list<string>|null */
    public ?array $events = null;
}
