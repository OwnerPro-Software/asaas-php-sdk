<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class WebhookVerifier
{
    public function __construct(
        #[SensitiveParameter]
        private string $authToken,
    ) {
        if ($authToken === '') {
            throw new InvalidArgumentException('The webhook auth token must not be empty.');
        }
    }

    public function verify(string $headerToken): bool
    {
        return hash_equals($this->authToken, $headerToken);
    }

    public function isFromAsaas(string $ip): bool
    {
        return in_array($ip, [
            '52.67.12.206',
            '18.230.8.159',
            '54.94.136.112',
            '54.94.183.101',
        ], true);
    }
}
