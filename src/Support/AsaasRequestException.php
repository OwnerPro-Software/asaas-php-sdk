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
        $message = $errors[0]['description'] ?? 'Asaas API error';
        parent::__construct($message, $this->statusCode);
    }
}
