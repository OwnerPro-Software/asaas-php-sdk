<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

/**
 * Every request-sending method returns an `AsaasResult` for definitive API
 * answers — a 2xx the SDK could read, or a 4xx verdict from Asaas. Anything
 * that is not an answer (no response, an unreadable one, or a 5xx) throws a
 * `TransportException` subclass, so an unknown outcome can never be mistaken
 * for a rejection. The one 4xx that is not a verdict is the rate limit: a 429
 * throws {@see RateLimitedException} — deliberately outside the
 * `TransportException` family, since the request was answered but never
 * processed — so a `catch (TransportException)` alone does not cover it.
 */
interface Connector
{
    /**
     * @param  array<string, mixed>  $query
     *
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function get(string $path, array $query = []): AsaasResult;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function post(string $path, array $data = []): AsaasResult;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function put(string $path, array $data = []): AsaasResult;

    /**
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function delete(string $path): AsaasResult;

    /**
     * Implementations MUST coerce any PHP `bool` values in $data to the literal strings
     * `'true'` / `'false'` before sending. Guzzle's MultipartStream encodes `false` as an
     * empty body, which Asaas treats as a missing field — boolean form fields like
     * `simplesNacional`, `enabled`, and `availableAfterPayment` would silently drop.
     *
     * `filename` is required, not optional: omitting it makes Guzzle fall back
     * to the stream's `uri` metadata and ship the local file's name, which is
     * the disclosure {@see ContentDispositionGuard::filename()} exists to
     * prevent — and the fallback happens past every guard. A part that is not a
     * file belongs in `$data`.
     *
     * @param  array<string, mixed>  $data
     * @param array<int, array{
     *     name: string,
     *     contents: string|resource,
     *     filename: string,
     *     headers?: array<array-key, string|int|float|bool>
     * }> $files
     *
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function postMultipart(string $path, array $data, array $files = []): AsaasResult;

    /**
     * @param  array<string, mixed>  $query
     *
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function paginate(string $path, array $query): AsaasPaginatedResult;

    /**
     * Lazy iterator that auto-paginates through all pages.
     *
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     *
     * @throws TransportException when no readable answer was received
     * @throws RateLimitedException when Asaas rate limited the request (429); nothing was processed
     */
    public function all(string $path, array $filters): Generator;
}
