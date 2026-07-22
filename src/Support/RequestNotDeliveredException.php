<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Throwable;

/**
 * The request provably never reached the Asaas API: name resolution,
 * TCP connect, or TLS handshake failed before any HTTP bytes were sent.
 * The operation was **not** processed — a direct retry is safe.
 */
final class RequestNotDeliveredException extends TransportException
{
    /** @param 'connect'|'dns'|'tls' $phase */
    public function __construct(
        public readonly string $phase,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('The request never reached the Asaas API (%s failure); it is safe to retry.', $phase),
            0,
            $previous,
        );
    }

    /**
     * The redacted view. See {@see TransportException} for why the property
     * list cannot be shown: `previous` reaches the API key.
     *
     * @return array{message: string, phase: string, file: string, line: int}
     */
    public function __debugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'phase' => $this->phase,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
