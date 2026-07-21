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
 * to 350 chars with HTML stripped. The empty-array case is synthesized too,
 * so `$result->errors[0]['description']` is always populated.
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

        if (! is_array($errors)) {
            $message = $response->json('message');

            if (is_string($message) && $message !== '') {
                return [['code' => 'UNKNOWN_ERROR', 'description' => $message]];
            }

            $body = mb_substr(strip_tags($response->body()), 0, 350);

            return [['code' => 'UNKNOWN_ERROR', 'description' => $body]];
        }

        if ($errors === []) {
            return [['code' => 'UNKNOWN_ERROR', 'description' => sprintf('Asaas returned empty errors array (status %d)', $response->status())]];
        }

        /** @var non-empty-list<array{code?: string, description?: string}> $errors */
        return $errors;
    }
}
