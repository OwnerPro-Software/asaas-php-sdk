<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use OwnerPro\Asaas\FiscalInfo\Request\FiscalInfoRequest;

/**
 * Replaces credential-bearing fields of an Asaas **response** payload with a
 * placeholder.
 *
 * Redaction everywhere else in the SDK is per-class: a DTO knows its own
 * secrets and names them in `__debugInfo()`. A response has no such class — it
 * arrives as a decoded array whose shape belongs to Asaas, not to the SDK — so
 * the field name is the only handle available.
 *
 * Five names carry a credential:
 *
 * - `apiKey` — the subaccount key returned once by `POST /accounts`
 * - `accessToken` — returned by the `/accounts/{id}/accessTokens` endpoints
 * - `authToken` — the webhook shared secret echoed by `GET /webhooks`
 * - `creditCardToken` — the reusable card token from the tokenization endpoints
 * - `username` — the municipal-portal login echoed by the `/fiscalInfo/`
 *   endpoints
 *
 * The first four grant the same authority as the value they stand in for, so a
 * result printed into a log or an error page is a credential disclosure.
 *
 * `username` is the one entry that is not a credential on its own — it is half
 * of one, and the SDK already treats it as sensitive on the way out:
 * {@see FiscalInfoRequest} marks it
 * `#[SensitiveParameter]` and masks it in `__debugInfo()`. Redacting the
 * request while printing the response in full is not a defensible place to
 * draw the line. It is the only field named `username` anywhere in
 * `specs/domains/`, so nothing else is caught by the name.
 */
final class SecretRedactor
{
    public const string PLACEHOLDER = '***';

    /** Stands in for a body that decoded as JSON but could not be re-encoded. */
    public const string UNENCODABLE = '*** <redacted; the body could not be re-encoded after scrubbing>';

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
        'username', // @pest-mutate-ignore
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
     * A body that *did* decode is never answered raw, not even when re-encoding
     * it fails. `json_decode()` accepts inputs `json_encode()` refuses — a float
     * literal past the double range decodes to `INF`, which cannot be encoded —
     * and both callers fall back to the untouched body on null. Answering null
     * there would hand back the very credential the scrub had already found and
     * replaced, so the failure answers a placeholder instead: fail closed, since
     * the one thing known about this body is that it decoded far enough to be
     * walked.
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

        return is_string($encoded) ? $encoded : self::UNENCODABLE;
    }

    /**
     * Exposed for callers whose values are not one-per-field — a header map
     * holds a *list* of values per name, so {@see self::scrub()} would replace
     * the list with a single placeholder and break the shape. They ask the
     * question here and substitute in their own shape.
     */
    public static function isSecretName(string $name): bool
    {
        return in_array(strtolower($name), self::SECRET_KEYS, strict: true);
    }

    private static function isSecret(int|string $key): bool
    {
        return is_string($key) && self::isSecretName($key);
    }
}
