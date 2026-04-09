<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class WebhookVerifier
{
    /** @var list<string> */
    public const DEFAULT_IPS = [
        '52.67.12.206', // @pest-mutate-ignore
        '18.230.8.159', // @pest-mutate-ignore
        '54.94.136.112', // @pest-mutate-ignore
        '54.94.183.101', // @pest-mutate-ignore
    ];

    /** @var list<string> */
    private array $trustedIps;

    /** @param list<string> $trustedIps */
    public function __construct(
        #[SensitiveParameter]
        private string $authToken,
        array $trustedIps = self::DEFAULT_IPS,
    ) {
        if ($authToken === '') {
            throw new InvalidArgumentException('The webhook auth token must not be empty.');
        }

        $this->trustedIps = $trustedIps;
    }

    public function verify(string $headerToken): bool
    {
        return hash_equals($this->authToken, $headerToken);
    }

    public function isFromAsaas(string $ip): bool
    {
        return in_array($ip, $this->trustedIps, true);
    }
}
