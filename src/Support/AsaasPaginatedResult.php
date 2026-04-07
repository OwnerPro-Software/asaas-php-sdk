<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Closure;

final readonly class AsaasPaginatedResult
{
    use ThrowsOnFailure;

    /**
     * @param  list<array<string, mixed>>  $data
     * @param  ?list<array{code?: string, description?: string}>  $errors
     * @param  ?(Closure(int): self)  $nextPageFetcher
     */
    public function __construct(
        public bool $success,
        public array $data,
        public int $totalCount,
        public bool $hasMore,
        public int $limit,
        public int $offset,
        public ?array $errors,
        public ?RawResponse $response,
        private ?Closure $nextPageFetcher,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $data
     * @param  ?Closure(int): self  $nextPageFetcher
     */
    public static function success(
        array $data,
        int $totalCount,
        bool $hasMore,
        int $limit,
        int $offset,
        RawResponse $rawResponse,
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
            response: $rawResponse,
            nextPageFetcher: $nextPageFetcher,
        );
    }

    /** @param list<array{code?: string, description?: string}> $errors */
    public static function failure(array $errors, ?RawResponse $rawResponse = null): self
    {
        return new self(
            success: false,
            data: [],
            totalCount: 0,
            hasMore: false,
            limit: 0,
            offset: 0,
            errors: $errors,
            response: $rawResponse,
            nextPageFetcher: null,
        );
    }

    public function next(): ?self
    {
        if (! $this->hasMore || $this->limit < 1 || ! $this->nextPageFetcher instanceof Closure) {
            return null;
        }

        return ($this->nextPageFetcher)($this->offset + $this->limit);
    }
}
