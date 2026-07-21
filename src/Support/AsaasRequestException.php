<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final class AsaasRequestException extends AsaasException
{
    public readonly int $statusCode;

    /** @param list<array{code?: string, description?: string}> $errors */
    public function __construct(
        public readonly array $errors,
        public readonly ?RawResponse $response,
    ) {
        $this->statusCode = $this->response?->status() ?? 0;
        // `?:` rather than `??`: Asaas's canonical envelope reaches the caller
        // verbatim, so a row carrying `"description": ""` would otherwise leave
        // the exception — and the log line it produces — with no message at all.
        $message = ($errors[0]['description'] ?? '') ?: 'Asaas API error';
        parent::__construct($message, $this->statusCode);
    }
}
