<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Generator;

interface Connector
{
    /**
     * @param  array<string, mixed>  $query
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function get(string $path, array $query, string $responseClass): AsaasResult;

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function post(string $path, array $data, string $responseClass): AsaasResult;

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function put(string $path, array $data, string $responseClass): AsaasResult;

    /** @param class-string<BaseResponse> $responseClass */
    public function delete(string $path, string $responseClass): AsaasResult;

    /**
     * @param  array<string, mixed>  $query
     * @param  class-string<BaseResponse>  $responseClass
     */
    public function paginate(string $path, array $query, string $responseClass): AsaasPaginatedResult;

    /**
     * Lazy iterator that auto-paginates through all pages.
     *
     * @template T of BaseResponse
     *
     * @param  array<string, mixed>  $filters
     * @param  class-string<T>  $responseClass
     * @return Generator<int, T>
     */
    public function all(string $path, array $filters, string $responseClass): Generator;
}
