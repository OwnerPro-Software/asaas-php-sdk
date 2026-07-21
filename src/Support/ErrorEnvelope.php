<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Illuminate\Http\Client\Response;

/**
 * Normalizes the upstream error envelope into the shape `AsaasResult` exposes.
 *
 * The returned list is **best-effort**: when Asaas (or an intermediary proxy)
 * returns the canonical envelope (`{"errors": [{"code", "description"}]}`)
 * each item carries `code` and `description`. Otherwise the SDK falls back
 * to a synthesized `UNKNOWN_ERROR` row whose `description` is either the
 * upstream `message` field (alternative shape) or the response body trimmed
 * to 350 chars with HTML stripped. The empty-array, object-shaped and
 * empty-body cases are synthesized too, so every **synthesized** row carries a
 * non-empty `description`.
 *
 * Canonical rows are passed through untouched — upstream owns their contents,
 * and rewriting them would hide what Asaas actually said. A canonical row may
 * therefore carry an absent or empty `description`; `AsaasRequestException`
 * substitutes its own message in that case.
 *
 * @internal Extraction detail of `AsaasConnector` — not part of the SDK's
 * public contract; consume errors via `AsaasResult::$errors`.
 */
final class ErrorEnvelope
{
    /** @return non-empty-list<array{code?: string, description?: string}> */
    public static function extract(Response $response): array
    {
        $errors = $response->json('errors');

        // A list of error objects is the canonical envelope. Anything else —
        // absent, a scalar, a single error object rather than a list of them, or
        // a list carrying scalars — falls back, because callers read
        // `$errors[0]['description']` and only that shape has an offset to read.
        if (! is_array($errors) || ! array_is_list($errors)) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => self::describe($response)]];
        }

        if ($errors === []) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => sprintf('Asaas returned empty errors array (status %d)', $response->status())]];
        }

        // Readable rows are kept even when a sibling is not one. Dropping the
        // whole envelope over a single malformed entry throws away the
        // `invalid_cpfCnpj` the caller actually needed and replaces it with a
        // dump of the body; falling back is only right when *nothing* in the
        // list can be read as an error object.
        $readable = array_values(array_filter($errors, self::isErrorObject(...)));

        if ($readable === []) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => self::describe($response)]];
        }

        /** @var non-empty-list<array{code?: string, description?: string}> $readable */
        return $readable;
    }

    /**
     * A row has to be a JSON *object* — the shape callers read `code` and
     * `description` off. `{"errors": [[1, 2]]}` gives a list-shaped row: it
     * survives an `is_array()` check and would then be passed through as if its
     * int-keyed entries were those fields, which is what the `@var` on the
     * pass-through asserts. It is no more readable as an error object than a
     * scalar is, so it is dropped the same way.
     *
     * The empty array is the exception, and not a list here despite what
     * `array_is_list()` says about it: `{}` and `[]` both decode to it, and the
     * first is a canonical row that simply carries no fields. `AsaasRequestException`
     * already substitutes its own message for a row with no `description`.
     */
    private static function isErrorObject(mixed $error): bool
    {
        return is_array($error) && ($error === [] || ! array_is_list($error));
    }

    /**
     * Never returns an empty string: a proxy, gateway or WAF answering with a
     * body that is blank or pure markup would otherwise produce an
     * `AsaasRequestException` carrying no message, leaving the caller's log with
     * nothing to act on.
     */
    private static function describe(Response $response): string
    {
        $message = $response->json('message');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        $body = trim(mb_substr(strip_tags(self::redactedBody($response)), 0, 350));

        if ($body !== '') {
            return $body;
        }

        return sprintf('Asaas returned status %d with no readable error body.', $response->status());
    }

    /**
     * The fallback description is the response body, and a rejected
     * `POST /accounts` answers with the subaccount payload — `apiKey` included.
     * `$errors` is the one part of a result that is *not* scrubbed downstream:
     * {@see AsaasResult::__debugInfo()} scrubs `data` by field name, and a
     * credential pasted into a description is a string, not a field it can
     * recognise. Scrubbing here is what keeps `dump($result)` and
     * `Log::info(['result' => $result])` from printing a live key beside the
     * `***` that {@see RawResponse::__debugInfo()} shows for the same bytes.
     *
     * Scrubbed before truncation, for the reason given in
     * {@see RawResponse::__debugInfo()}: cutting first leaves JSON that
     * {@see SecretRedactor::scrubJson()} can no longer parse. A body that is
     * not JSON at all has no field names to key on and is returned as-is.
     */
    private static function redactedBody(Response $response): string
    {
        $body = $response->body();

        return SecretRedactor::scrubJson($body) ?? $body;
    }
}
