<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;

/**
 * Base type for transport-level failures: no complete, readable response was
 * received from the Asaas API — or the response received was a 5xx, which
 * reports that the server could not answer. Catching a subclass tells the
 * caller whether a blind retry is safe (`RequestNotDeliveredException`) or
 * whether reconciliation is required first (`IndeterminateResultException`).
 *
 * These are the one exception family that holds a foreign object graph.
 * {@see TransportFailureClassifier} keeps the original `ConnectionException`
 * in `getPrevious()` — the errno behind the verdict is only readable there —
 * and Laravel builds that exception around the Guzzle one, which holds the
 * PSR-7 request, which carries the `access_token` header. So the property list
 * of a transport failure reaches the live API key in two hops, and the whole
 * point of `catch (RequestNotDeliveredException $e)` is that a caller is
 * holding this object when something has gone wrong and is about to look
 * inside it.
 *
 * Redaction therefore cannot be a matter of naming secret fields: there are
 * none on these classes. It has to replace the property list outright, which
 * is what `__debugInfo()` does for `var_dump()`/`print_r()` and what the
 * {@see Redactable} caster does for `dump()`, `dd()` and the Ignition page.
 * Each subclass restates its own diagnostics — message, phase, response, file
 * and line — because a redacted view that drops them costs the reader the
 * reason they dumped the exception.
 *
 * `serialize()` and `var_export()` still walk the real graph; both are
 * documented in README's debug-output table as the paths that read private
 * state directly.
 */
abstract class TransportException extends AsaasException implements JsonSerializable, Redactable
{
    /**
     * Covers `Log::error('transport failed', ['e' => $e])`, which encodes the
     * exception rather than printing it.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }
}
