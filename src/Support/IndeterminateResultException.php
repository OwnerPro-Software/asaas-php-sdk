<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Throwable;

/**
 * The request may or may not have been processed by the Asaas API: it timed
 * out waiting for the response, the connection dropped mid-transfer, or a 2xx
 * arrived whose body is not the JSON object the SDK can interpret (invalid
 * JSON, empty non-204 body, or a bare scalar). **Never** retry blindly — reconcile first
 * (e.g. via the Asaas withdrawal-validation webhook or a lookup by your own
 * identifier). `phase` is null when the failure point could not be proven.
 */
final class IndeterminateResultException extends TransportException
{
    /**
     * @param  'body'|'read'|'transfer'|null  $phase
     * @param  ?RawResponse  $response  the received-but-uninterpretable HTTP
     *                                  response — populated only for `phase: 'body'`,
     *                                  where a 2xx arrived; null for failures where
     *                                  no complete response was received
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
}
