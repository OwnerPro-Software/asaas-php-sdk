<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use JsonSerializable;

/**
 * Public read-only view of the HTTP response carried by results and
 * transport exceptions. The underlying `Illuminate` Response is intentionally
 * not exposed to prevent API key leakage via request headers.
 *
 * Not exposed means not *held*. An `Illuminate\Http\Client\Response` reaches
 * the `TransferStats` of the call that produced it, and through those the
 * **request** headers — the `access_token`. A private property is enough to
 * stop every reader that asks the object what it is: `dump()`, `var_dump()`,
 * `json_encode()` and `print_r()` all route through the redacted view. It is
 * not enough for `var_export()`, which walks private properties directly and
 * answers nothing when asked. So the three things this class actually reads
 * are copied out at construction and the response itself is dropped: what is
 * not held cannot be walked to.
 *
 * Copying is safe because all three are already buffered — Guzzle has read the
 * body into memory by the time a `Response` exists — and because this view is
 * immutable by construction anyway.
 */
final readonly class RawResponse implements JsonSerializable, Redactable
{
    private int $status;

    /** @var array<string, list<string>> */
    private array $headers;

    private string $body;

    public function __construct(Response $response)
    {
        /** @var array<string, list<string>> $headers */
        $headers = $response->headers();

        $this->status = $response->status();
        $this->headers = $headers;
        $this->body = $response->body();
    }

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
        $body = SecretRedactor::scrubJson($this->body) ?? $this->body;
        $length = mb_strlen($body);
        $limit = 350;

        return [
            'status' => $this->status,
            'headers' => $this->redactedHeaders(),
            'body' => $length <= $limit
                ? $body
                : mb_substr($body, 0, $limit).'... <truncated; '.$length.' chars total>',
        ];
    }

    /**
     * `AsaasResult::jsonSerialize()` hands this object on whole, and
     * `json_encode()` walks *public* properties — of which there are none here,
     * so the encoded result carried `"response":{}` where the redacted view was
     * meant to be. The dump path had the caster; this one had nothing.
     *
     * @return array{status: int, headers: array<string, list<string>>, body: string}
     */
    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Header names are case-insensitive per RFC 7230, and the name a server
     * chose to send is not the name a caller will ask for — Asaas answers
     * `Content-Disposition` while the SDK's own reader asks for
     * `content-disposition`. A repeated field is joined the way PSR-7 joins it,
     * which is the behaviour this replaced.
     */
    public function header(string $key): ?string
    {
        foreach ($this->headers as $name => $values) {
            if (strcasecmp($name, $key) === 0) {
                $value = implode(', ', $values);

                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    public function body(): string
    {
        return $this->body;
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

        foreach ($this->headers as $name => $values) {
            $redacted[$name] = SecretRedactor::isSecretName($name)
                ? [SecretRedactor::PLACEHOLDER]
                : $values;
        }

        return $redacted;
    }
}
