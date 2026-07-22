<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Asaas refused the request before processing it: the account exceeded its
 * rate limit. Nothing moved — no charge was created, no money left the
 * account — so the operation is safe to repeat once the window reopens.
 *
 * This is neither a verdict nor a transport failure, which is why it is
 * neither an `AsaasRequestException` (Asaas rejecting *this* operation) nor a
 * {@see TransportException} (the request travelled and was answered). Treat it
 * as "not delivered": leave the caller's own state untouched and back off.
 */
final class RateLimitedException extends AsaasException
{
    /**
     * Seconds the caller should wait, when Asaas states one.
     *
     * `Retry-After` is defined as either a delay in seconds or an HTTP-date
     * (RFC 9110 §10.2.3). Only the delay form is read here: converting a date
     * needs a clock, and a wrong clock turns a backoff into a hot loop. When
     * the header is absent, a date, or anything else, this is null and the raw
     * value stays available through `$response->header('Retry-After')`.
     */
    public readonly ?int $retryAfter;

    public function __construct(public readonly RawResponse $response)
    {
        $this->retryAfter = $this->delaySeconds($response->header('Retry-After'));

        parent::__construct(
            'Asaas rate limited the request; it was not processed. Back off and retry.',
            $response->status(),
        );
    }

    private function delaySeconds(?string $header): ?int
    {
        if ($header === null || ! ctype_digit($header)) {
            return null;
        }

        return (int) $header;
    }
}
