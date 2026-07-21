<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

/**
 * Every request-sending method returns an `AsaasResult` for definitive API
 * answers (2xx/4xx/5xx). When the implementation is built with
 * `throwOnTransportFailure: true`, transport failures throw a
 * `TransportException` subclass instead of being folded into a failure
 * result; with the flag off (default), no transport exception is ever thrown.
 */
interface Connector
{
    /**
     * @param  array<string, mixed>  $query
     *
     * @throws TransportException only when built with `throwOnTransportFailure: true`
     */
    public function get(string $path, array $query = []): AsaasResult;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TransportException only when built with `throwOnTransportFailure: true`
     */
    public function post(string $path, array $data = []): AsaasResult;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TransportException only when built with `throwOnTransportFailure: true`
     */
    public function put(string $path, array $data = []): AsaasResult;

    /** @throws TransportException only when built with `throwOnTransportFailure: true` */
    public function delete(string $path): AsaasResult;

    /**
     * Implementations MUST coerce any PHP `bool` values in $data to the literal strings
     * `'true'` / `'false'` before sending. Guzzle's MultipartStream encodes `false` as an
     * empty body, which Asaas treats as a missing field — boolean form fields like
     * `simplesNacional`, `enabled`, and `availableAfterPayment` would silently drop.
     *
     * @param  array<string, mixed>  $data
     * @param array<int, array{
     *     name: string,
     *     contents: string|resource,
     *     filename?: string,
     *     headers?: array<string, string>
     * }> $files
     *
     * @throws TransportException only when built with `throwOnTransportFailure: true`
     */
    public function postMultipart(string $path, array $data, array $files = []): AsaasResult;

    /**
     * @param  array<string, mixed>  $query
     *
     * @throws TransportException only when built with `throwOnTransportFailure: true`
     */
    public function paginate(string $path, array $query): AsaasPaginatedResult;

    /**
     * Lazy iterator that auto-paginates through all pages.
     *
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     *
     * @throws TransportException only when built with `throwOnTransportFailure: true`
     */
    public function all(string $path, array $filters): Generator;
}
