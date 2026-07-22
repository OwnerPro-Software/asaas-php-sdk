<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;

/**
 * A walk over `all()` ended on a fault the SDK itself diagnosed — a page that
 * contradicted the envelope's own `totalCount`, repeated the previous page,
 * stalled, or ran away. The rows carry a `PAGINATION_*` code and a description
 * written by this SDK, not by Asaas.
 *
 * It is deliberately not an {@see AsaasRequestException}: that type means
 * "Asaas answered with a verdict", and since 3.0 it only ever carries a 4xx.
 * A pagination fault has no verdict behind it — the responses that produced it
 * were 200s, or there was no response at all — so relaying it as one would
 * have meant an `AsaasRequestException` whose `statusCode` states nothing.
 *
 * `$response` is the page the walk stopped on, when there is one.
 */
final class AsaasPaginationException extends AsaasException implements JsonSerializable
{
    /** @param list<array{code?: string, description?: string}> $errors */
    public function __construct(
        public readonly array $errors,
        public readonly ?RawResponse $response,
        public readonly int $offset,
        public readonly int $limit,
    ) {
        parent::__construct($errors[0]['description'] ?? 'Asaas pagination fault');
    }

    /**
     * Unlike {@see AsaasRequestException}, the rows here are authored by this
     * SDK and cannot carry a credential, so there is nothing to scrub —
     * `$response` redacts itself. What this restores is the exception's own
     * diagnostics, which the {@see Redactable} caster on the nested response
     * leaves alone but `jsonSerialize()` would otherwise drop.
     *
     * @return array{message: string, errors: list<array{code?: string, description?: string}>, response: ?RawResponse, offset: int, limit: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'response' => $this->response,
            'offset' => $this->offset,
            'limit' => $this->limit,
        ];
    }
}
