<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class WebhookVerifier
{
    /** @var list<string> */
    public const array DEFAULT_IPS = [
        '52.67.12.206', // @pest-mutate-ignore
        '18.230.8.159', // @pest-mutate-ignore
        '54.94.136.112', // @pest-mutate-ignore
        '54.94.183.101', // @pest-mutate-ignore
    ];

    /** The 12-byte prefix every IPv4-mapped IPv6 address carries: `::ffff:a.b.c.d`. */
    private const string IPV4_MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    /** @param list<string> $trustedIps */
    public function __construct(
        #[SensitiveParameter]
        private string $authToken,
        private array $trustedIps = self::DEFAULT_IPS,
    ) {
        if ($authToken === '') {
            throw new InvalidArgumentException('The webhook auth token must not be empty.');
        }
    }

    public function verify(string $headerToken): bool
    {
        return hash_equals($this->authToken, $headerToken);
    }

    /**
     * Compares packed addresses rather than the literal strings a proxy
     * happened to produce. A dual-stack listener reports an IPv4 client as the
     * IPv4-mapped form `::ffff:52.67.12.206`, which is the same host as
     * `52.67.12.206` but never string-equal to it — a raw comparison rejects
     * every genuine webhook and silently stops reconciliation.
     */
    public function isFromAsaas(string $ip): bool
    {
        $packed = $this->pack($ip);

        if ($packed === null) {
            return false;
        }

        foreach ($this->trustedIps as $trustedIp) {
            if ($this->pack($trustedIp) === $packed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Packs an address into its canonical bytes, folding the IPv4-mapped IPv6
     * range down to its 4-byte IPv4 form. Returns null for anything that is not
     * an IP address, so a malformed value never matches an allowlist entry.
     */
    private function pack(string $ip): ?string
    {
        $packed = inet_pton($ip);

        if (! is_string($packed)) {
            return null;
        }

        if (strlen($packed) === 16 && str_starts_with($packed, self::IPV4_MAPPED_PREFIX)) {
            return substr($packed, 12);
        }

        return $packed;
    }
}
