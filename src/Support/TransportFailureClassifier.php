<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;

/**
 * Maps a transport failure to the typed exception contract.
 *
 * Classification rule: certainty is mandatory, bias toward indeterminate.
 * `RequestNotDeliveredException` is produced only on unequivocal evidence
 * that no HTTP bytes reached the API (cURL 6/7/35/58/60). Anything ambiguous
 * — including a missing or foreign previous exception — classifies as
 * indeterminate, because the costs are asymmetric: a mislabelled "not
 * delivered" invites a blind retry of an operation that may have moved money.
 *
 * cURL 28 (timeout) is ALWAYS indeterminate: `connect_time == 0.0` cannot
 * prove a connect-phase timeout, because on a reused keep-alive connection
 * curl reports connection timers as 0 even though the request was sent
 * (curl issue #2703) — and the SDK reuses its `PendingRequest` across calls.
 */
final class TransportFailureClassifier
{
    public static function classify(ConnectionException $connectionException): TransportException
    {
        $context = self::curlContext($connectionException);
        $errnoValue = $context['errno'] ?? null;

        $errno = is_int($errnoValue) ? $errnoValue : null;

        return match (true) {
            $errno === 6 => new RequestNotDeliveredException('dns', $connectionException), // CURLE_COULDNT_RESOLVE_HOST
            $errno === 7 => new RequestNotDeliveredException('connect', $connectionException), // CURLE_COULDNT_CONNECT
            $errno === 35, // CURLE_SSL_CONNECT_ERROR
            $errno === 58, // CURLE_SSL_CERTPROBLEM
            $errno === 60 => new RequestNotDeliveredException('tls', $connectionException), // CURLE_PEER_FAILED_VERIFICATION
            $errno === 28, // CURLE_OPERATION_TIMEDOUT — always indeterminate, see class docblock
            $errno === 52 => new IndeterminateResultException('read', $connectionException), // CURLE_GOT_NOTHING
            $errno === 18, // CURLE_PARTIAL_FILE
            $errno === 55, // CURLE_SEND_ERROR
            $errno === 56, // CURLE_RECV_ERROR
            $errno === 92 => new IndeterminateResultException('transfer', $connectionException), // CURLE_HTTP2_STREAM
            default => new IndeterminateResultException(null, $connectionException),
        };
    }

    /**
     * Extracts the cURL handler context from the wrapped Guzzle exception.
     * Laravel wraps both `ConnectException` and response-less
     * `RequestException` into its `ConnectionException`, keeping the original
     * in `getPrevious()`; the Guzzle handler context carries `errno` plus the
     * full `curl_getinfo()` snapshot.
     *
     * @return array<array-key, mixed>
     */
    private static function curlContext(ConnectionException $connectionException): array
    {
        $previous = $connectionException->getPrevious();

        if (! $previous instanceof ConnectException && ! $previous instanceof RequestException) {
            return [];
        }

        return $previous->getHandlerContext();
    }
}
