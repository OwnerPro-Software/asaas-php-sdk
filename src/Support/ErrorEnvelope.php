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
 * empty-body cases are synthesized too, so `$result->errors[0]['description']`
 * is always a non-empty string.
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
        if (! self::isCanonical($errors)) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => self::describe($response)]];
        }

        if ($errors === []) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => sprintf('Asaas returned empty errors array (status %d)', $response->status())]];
        }

        /** @var non-empty-list<array{code?: string, description?: string}> $errors */
        return $errors;
    }

    /**
     * @phpstan-assert-if-true list<array<string, mixed>> $errors
     */
    private static function isCanonical(mixed $errors): bool
    {
        if (! is_array($errors) || ! array_is_list($errors)) {
            return false;
        }

        foreach ($errors as $error) {
            if (! is_array($error)) {
                return false;
            }
        }

        return true;
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

        $body = trim(mb_substr(strip_tags($response->body()), 0, 350));

        if ($body !== '') {
            return $body;
        }

        return sprintf('Asaas returned status %d with no readable error body.', $response->status());
    }
}
