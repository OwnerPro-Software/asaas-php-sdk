<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Throwable;

/**
 * The request may or may not have been processed by the Asaas API: it timed
 * out waiting for the response, the connection dropped mid-transfer, a 2xx
 * arrived whose body is not the JSON object the SDK can interpret (invalid
 * JSON, empty non-204 body, or a bare scalar), the server answered 5xx — which
 * reports that it could not answer, not that the operation was refused — it
 * answered 408, reporting that it gave up waiting for a request it may already
 * have been processing, or it answered 3xx, which Asaas never does: something
 * in front of the API replied in its place.
 * **Never** retry blindly — reconcile first
 * (e.g. via the Asaas withdrawal-validation webhook or a lookup by your own
 * identifier). `phase` is null when the failure point could not be proven.
 */
final class IndeterminateResultException extends TransportException
{
    /**
     * @param  'body'|'read'|'redirect'|'server'|'timeout'|'transfer'|null  $phase
     * @param  ?RawResponse  $response  the received-but-uninterpretable HTTP
     *                                  response — populated for `phase: 'body'`
     *                                  (a 2xx arrived), `phase: 'server'` (a
     *                                  5xx arrived), `phase: 'timeout'` (a 408
     *                                  arrived) and `phase: 'redirect'` (a 3xx
     *                                  arrived); null for failures where no
     *                                  complete response was received
     */
    public function __construct(
        public readonly ?string $phase = null,
        ?Throwable $previous = null,
        public readonly ?RawResponse $response = null,
    ) {
        parent::__construct(
            'The Asaas API may or may not have processed the request; reconcile before retrying.',
            0,
            $previous,
        );
    }

    /**
     * The redacted view. See {@see TransportException} for why the property
     * list cannot be shown: `previous` reaches the API key. `response` is a
     * {@see RawResponse}, which redacts itself on the way out.
     *
     * @return array{message: string, phase: ?string, response: ?RawResponse, file: string, line: int}
     */
    public function __debugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'phase' => $this->phase,
            'response' => $this->response,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
