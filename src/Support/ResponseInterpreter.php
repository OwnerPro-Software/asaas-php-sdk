<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Illuminate\Http\Client\Response;

/**
 * Decides what a received HTTP response means: a definitive success, a
 * definitive rejection, or an indeterminate outcome the caller must reconcile.
 *
 * This is the pure half of the connector — no I/O, no client state — so the
 * rule that separates "Asaas said no" from "the server could not say" is
 * testable and lives in one place.
 */
final class ResponseInterpreter
{
    public static function toResult(Response $response, bool $throwOnTransportFailure): AsaasResult
    {
        $rawResponse = new RawResponse($response);

        // A 5xx is not the Asaas API answering about the operation — it is the
        // server (or a proxy in front of it) saying it could not answer. The
        // request may well have been processed, so it belongs to the
        // indeterminate category. Only 4xx carries an actual verdict.
        if ($throwOnTransportFailure && $response->serverError()) {
            throw new IndeterminateResultException('server', response: $rawResponse);
        }

        if ($response->failed()) {
            return AsaasResult::failure(ErrorEnvelope::extract($response), $rawResponse);
        }

        return AsaasResult::success(self::data($response, $rawResponse, $throwOnTransportFailure), $rawResponse);
    }

    /** @return array<string, mixed> */
    private static function data(Response $response, RawResponse $rawResponse, bool $throwOnTransportFailure): array
    {
        $json = $response->json();

        if (is_array($json)) {
            /** @var array<string, mixed> $json */
            return $json;
        }

        // 204 No Content is a definitive success with an intentionally empty
        // body (e.g. deleteAccessToken, removeBackoff) — never an
        // unreadable-body transport failure.
        if ($throwOnTransportFailure && $response->status() !== 204) {
            throw new IndeterminateResultException('body', response: $rawResponse);
        }

        return [];
    }
}
