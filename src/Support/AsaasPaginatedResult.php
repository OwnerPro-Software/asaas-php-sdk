<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Closure;

final class AsaasPaginatedResult
{
    /**
     * @param  list<BaseResponse>  $data
     * @param  ?list<array{code?: string, description?: string}>  $errors
     * @param  ?(Closure(int): self)  $nextPageFetcher
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $data,
        public readonly int $totalCount,
        public readonly bool $hasMore,
        public readonly int $limit,
        public readonly int $offset,
        public readonly ?array $errors,
        public readonly int $statusCode,
        private readonly ?Closure $nextPageFetcher,
    ) {}

    /**
     * @param  list<BaseResponse>  $data
     * @param  ?Closure(int): self  $nextPageFetcher
     */
    public static function success(
        array $data,
        int $totalCount,
        bool $hasMore,
        int $limit,
        int $offset,
        int $statusCode,
        ?Closure $nextPageFetcher,
    ): self {
        return new self(
            success: true,
            data: $data,
            totalCount: $totalCount,
            hasMore: $hasMore,
            limit: $limit,
            offset: $offset,
            errors: null,
            statusCode: $statusCode,
            nextPageFetcher: $nextPageFetcher,
        );
    }

    /** @param list<array{code?: string, description?: string}> $errors */
    public static function failure(array $errors, int $statusCode): self
    {
        return new self(
            success: false,
            data: [],
            totalCount: 0,
            hasMore: false,
            limit: 0,
            offset: 0,
            errors: $errors,
            statusCode: $statusCode,
            nextPageFetcher: null,
        );
    }

    public function next(): ?self
    {
        if (! $this->hasMore || ! $this->nextPageFetcher instanceof Closure) {
            return null;
        }

        return ($this->nextPageFetcher)($this->offset + $this->limit);
    }

    public function throw(): self
    {
        if (! $this->success) {
            throw new AsaasRequestException($this->errors ?? [], $this->statusCode);
        }

        return $this;
    }
}
