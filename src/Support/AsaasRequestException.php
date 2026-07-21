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
        parent::__construct($this->describe($errors[0]['description'] ?? null), $this->statusCode);
    }

    /**
     * `ErrorEnvelope` passes a canonical row through verbatim, so `description`
     * is whatever Asaas — or a proxy impersonating it — put there. An empty
     * string would leave the log line with nothing to act on, and a non-string
     * would raise `TypeError` out of `Exception::__construct()`, escaping the
     * Result-based contract this exception exists to serve.
     */
    private function describe(mixed $description): string
    {
        return is_string($description) && $description !== ''
            ? $description
            : 'Asaas API error';
    }
}
