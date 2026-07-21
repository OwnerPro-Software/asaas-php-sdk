<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

/**
 * Public read-only view of the HTTP response carried by results and
 * transport exceptions. The underlying `Illuminate` Response is intentionally
 * not exposed to prevent API key leakage via request headers.
 */
final readonly class RawResponse implements Redactable
{
    public function __construct(private Response $response) {}

    /**
     * The body is scrubbed before it is truncated, never after: truncation
     * leaves invalid JSON that {@see SecretRedactor::scrubJson()} cannot parse,
     * and an unparseable body falls through to its raw text — which is exactly
     * the credential this is meant to withhold.
     *
     * That fallback is the limit of what this can promise. The scrub keys on
     * field names, so it needs a body it can decode: a body that is not JSON,
     * that is a bare JSON scalar, or that nests deeper than `json_decode()`'s
     * default limit is shown as it arrived. Asaas's envelopes are shallow
     * objects, so this is a statement about proxies and gateways rather than
     * about Asaas.
     *
     * Headers go through the same scrub. Asaas puts no credential in one, but
     * this view is also shown for whatever answered in its place, and a proxy
     * echoing an `authToken` header is the sort of thing worth not printing.
     *
     * @return array{status: int, headers: array<string, list<string>>, body: string}
     */
    public function __debugInfo(): array
    {
        $body = SecretRedactor::scrubJson($this->body()) ?? $this->body();
        $length = mb_strlen($body);
        $limit = 350;

        return [
            'status' => $this->status(),
            'headers' => $this->redactedHeaders(),
            'body' => $length <= $limit
                ? $body
                : mb_substr($body, 0, $limit).'... <truncated; '.$length.' chars total>',
        ];
    }

    public function status(): int
    {
        return $this->response->status();
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        /** @var array<string, list<string>> */
        return $this->response->headers();
    }

    public function header(string $key): ?string
    {
        $value = $this->response->header($key);

        return $value === '' ? null : $value;
    }

    public function body(): string
    {
        return $this->response->body();
    }

    /** @param array<string, string> $headers */
    public static function fake(int $status = 200, array $headers = [], string $body = ''): self
    {
        return new self(new Response(new Psr7Response($status, $headers, $body)));
    }

    /**
     * A header field holds a *list* of values, so the placeholder replaces the
     * list rather than standing in its place: {@see SecretRedactor::scrub()}
     * answers one value per field and would hand back a bare string where the
     * caller — and this method's own return type — expects a list.
     *
     * @return array<string, list<string>>
     */
    private function redactedHeaders(): array
    {
        $redacted = [];

        foreach ($this->headers() as $name => $values) {
            $redacted[$name] = SecretRedactor::isSecretName($name)
                ? [SecretRedactor::PLACEHOLDER]
                : $values;
        }

        return $redacted;
    }
}
