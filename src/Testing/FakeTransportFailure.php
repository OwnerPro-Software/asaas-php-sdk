<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Testing;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use InvalidArgumentException;

/**
 * Builds the same exception shape a real transport failure produces: an
 * Illuminate `ConnectionException` wrapping a Guzzle `ConnectException` whose
 * handler context carries the cURL errno for the requested phase. Stubs built
 * here therefore flow through `TransportFailureClassifier` exactly like
 * production failures, so fakes honour the connector's
 * `throwOnTransportFailure` flag instead of bypassing it.
 */
final class FakeTransportFailure
{
    /** @param 'connect'|'dns'|'tls' $phase */
    public static function requestNotDelivered(string $phase): ConnectionException
    {
        return self::connectionException(self::context($phase, self::notDeliveredPhases()));
    }

    /** @param 'read'|'transfer' $phase */
    public static function indeterminateResult(string $phase): ConnectionException
    {
        return self::connectionException(self::context($phase, self::indeterminatePhases()));
    }

    /** @return array<string, array<string, int|float>> */
    private static function notDeliveredPhases(): array
    {
        return [
            'dns' => ['errno' => 6], // CURLE_COULDNT_RESOLVE_HOST
            'connect' => ['errno' => 7], // CURLE_COULDNT_CONNECT
            'tls' => ['errno' => 35], // CURLE_SSL_CONNECT_ERROR
        ];
    }

    /** @return array<string, array<string, int|float>> */
    private static function indeterminatePhases(): array
    {
        return [
            'read' => ['errno' => 28], // CURLE_OPERATION_TIMEDOUT
            'transfer' => ['errno' => 56], // CURLE_RECV_ERROR
        ];
    }

    /**
     * @param  array<string, array<string, int|float>>  $phases
     * @return array<string, int|float>
     */
    private static function context(string $phase, array $phases): array
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

    /** @param array<string, int|float> $context */
    private static function connectionException(array $context): ConnectionException
    {
        $connectException = new ConnectException(
            sprintf('Simulated transport failure (cURL error %d)', $context['errno']),
            new Request('POST', 'https://asaas.test/simulated'),
            null,
            $context,
        );

        return new ConnectionException($connectException->getMessage(), previous: $connectException);
    }
}
