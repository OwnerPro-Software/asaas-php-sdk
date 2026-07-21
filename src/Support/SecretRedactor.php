<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Replaces credential-bearing fields of an Asaas **response** payload with a
 * placeholder.
 *
 * Redaction everywhere else in the SDK is per-class: a DTO knows its own
 * secrets and names them in `__debugInfo()`. A response has no such class — it
 * arrives as a decoded array whose shape belongs to Asaas, not to the SDK — so
 * the field name is the only handle available.
 *
 * Four names carry a live credential:
 *
 * - `apiKey` — the subaccount key returned once by `POST /accounts`
 * - `accessToken` — returned by the `/accounts/{id}/accessTokens` endpoints
 * - `authToken` — the webhook shared secret echoed by `GET /webhooks`
 * - `creditCardToken` — the reusable card token from the tokenization endpoints
 *
 * Each grants the same authority as the value it stands in for, so a result
 * printed into a log or an error page is a credential disclosure.
 */
final class SecretRedactor
{
    public const string PLACEHOLDER = '***';

    /**
     * Lowercased for a case-insensitive match: the wire spells these in
     * camelCase, but a proxy or a future endpoint answering `apikey` would
     * otherwise walk a live key straight past the filter.
     *
     * @var list<string>
     */
    private const array SECRET_KEYS = [
        'apikey', // @pest-mutate-ignore
        'accesstoken', // @pest-mutate-ignore
        'authtoken', // @pest-mutate-ignore
        'creditcardtoken', // @pest-mutate-ignore
    ];

    /**
     * Walks nested arrays so a secret survives no matter how deep the envelope
     * buries it — paginated bodies carry their rows under `data[]`, and
     * `POST /accounts` nests the key inside the account object.
     *
     * A secret key is replaced before its value is inspected: a field that
     * arrives as an array rather than the documented string is still a secret,
     * and recursing into it would emit the parts unredacted.
     *
     * Keys are preserved verbatim — only values change — so a scrubbed list
     * still encodes as a JSON array and a scrubbed body keeps its field names.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, mixed>  $data
     * @return array<TKey, mixed>
     */
    public static function scrub(array $data): array
    {
        $scrubbed = [];

        foreach ($data as $key => $value) {
            $scrubbed[$key] = match (true) {
                self::isSecret($key) => self::PLACEHOLDER,
                is_array($value) => self::scrub($value),
                default => $value,
            };
        }

        return $scrubbed;
    }

    /**
     * Answers null when the body is not a JSON array or object: there is
     * nothing to walk, so the caller keeps its own raw-text handling instead of
     * receiving a lossy re-encode.
     *
     * The returned JSON is re-encoded rather than patched in place, so
     * insignificant whitespace from the wire is not preserved. This is debug
     * output; `body()` still answers the exact bytes Asaas sent.
     */
    public static function scrubJson(string $body): ?string
    {
        $decoded = json_decode($body, associative: true);

        if (! is_array($decoded)) {
            return null;
        }

        $encoded = json_encode(self::scrub($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : null;
    }

    private static function isSecret(int|string $key): bool
    {
        return is_string($key) && in_array(strtolower($key), self::SECRET_KEYS, strict: true);
    }
}
