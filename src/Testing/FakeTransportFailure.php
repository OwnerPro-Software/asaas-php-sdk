<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use GuzzleHttp\Exception\ConnectException;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * Builds the same exception a real transport failure produces: a Guzzle
 * `ConnectException` whose handler context carries the cURL errno for the
 * requested phase.
 *
 * Stubs throw the Guzzle exception rather than an Illuminate
 * `ConnectionException` so the failure flows through
 * `PendingRequest::marshalConnectionException()` exactly like a production
 * cURL error: the request/response pair is recorded on the factory (making it
 * visible to `assertSent`/`assertNotSent`) before the Illuminate exception is
 * raised. Throwing the Illuminate exception directly would skip that step and
 * leave the request invisible to the assertion helpers.
 *
 * The errno lookups are separate from the exception builder so callers can
 * validate a phase eagerly, at stub-registration time, while deferring
 * construction until the request is in hand.
 */
final class FakeTransportFailure
{
    /** @param 'connect'|'dns'|'tls' $phase */
    public static function notDeliveredErrno(string $phase): int
    {
        return self::errno($phase, self::notDeliveredPhases());
    }

    /** @param 'read'|'transfer' $phase */
    public static function indeterminateErrno(string $phase): int
    {
        return self::errno($phase, self::indeterminatePhases());
    }

    /**
     * An errno the classifier deliberately has no line for, so a stub built on
     * it lands on the default branch — `IndeterminateResultException` with a
     * null phase. cURL 95 is `CURLE_HTTP3`, real enough to arrive in
     * production and outside the map by design; if it ever gains a line there,
     * this must move to another unmapped one.
     */
    public static function unclassifiedErrno(): int
    {
        return 95;
    }

    public static function connectException(int $errno, RequestInterface $request): ConnectException
    {
        return new ConnectException(
            sprintf('Simulated transport failure (cURL error %d)', $errno),
            $request,
            null,
            ['errno' => $errno],
        );
    }

    /** @return array<string, int> */
    private static function notDeliveredPhases(): array
    {
        return [
            'dns' => 6, // CURLE_COULDNT_RESOLVE_HOST
            'connect' => 7, // CURLE_COULDNT_CONNECT
            'tls' => 35, // CURLE_SSL_CONNECT_ERROR
        ];
    }

    /** @return array<string, int> */
    private static function indeterminatePhases(): array
    {
        return [
            'read' => 28, // CURLE_OPERATION_TIMEDOUT
            'transfer' => 56, // CURLE_RECV_ERROR
        ];
    }

    /** @param array<string, int> $phases */
    private static function errno(string $phase, array $phases): int
    {
        if (! isset($phases[$phase])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown transport failure phase "%s"; expected one of: %s.',
                $phase,
                implode(', ', array_keys($phases)),
            ));
        }

        return $phases[$phase];
    }
}
