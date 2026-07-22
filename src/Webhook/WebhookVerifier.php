<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

use InvalidArgumentException;
use LogicException;
use OwnerPro\Asaas\Support\Redactable;
use SensitiveParameter;

final readonly class WebhookVerifier implements Redactable
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

    /**
     * `#[SensitiveParameter]` marks the constructor *parameter*, not the
     * promoted property, so it only ever swapped the argument out of a recorded
     * stack trace — and under PHP's default `zend.exception_ignore_args=1` it
     * did not even do that. The secret itself stayed readable on the object,
     * and this is the object built to hold it: the README has it constructed in
     * the controller that answers Asaas, so `dd($verifier)` while debugging a
     * rejected delivery printed the value authenticating every inbound webhook.
     *
     * The token is shown as `***` rather than partially masked, matching
     * `CreateWebhookRequest` and `UpdateWebhookRequest` — the two other places
     * this same secret is redacted. `trustedIps` is not a secret and stays
     * readable: which addresses are allowed is the other half of a rejected
     * webhook, and hiding it would make the dump useless for the case it is
     * reached for.
     *
     * @return array{authToken: string, trustedIps: list<string>}
     */
    public function __debugInfo(): array
    {
        return [
            'authToken' => '***',
            'trustedIps' => $this->trustedIps,
        ];
    }

    /**
     * Neither `serialize()` nor `var_export()` honours `__debugInfo()`.
     * Refusing serialization keeps the shared secret out of queue payloads,
     * caches and session data; `var_export()` is unguardable, so it must never
     * be pointed at a verifier (documented in the README).
     *
     * @return never
     */
    public function __serialize(): array
    {
        throw new LogicException(self::class.' cannot be serialized: it holds the webhook shared secret, which must never reach a queue, cache, or session payload. Rebuild it where you need it, reading the token from your own secret store.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return never
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException(self::class.' cannot be unserialized.');
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
