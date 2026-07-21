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
    public static function failure(array $errors, ?RawResponse $rawResponse = null, int $offset = 0, int $limit = 0): self
    {
        return new self(
            success: false,
            data: [],
            totalCount: 0,
            hasMore: false,
            limit: $limit,
            offset: $offset,
            errors: $errors,
            response: $rawResponse,
            nextPageFetcher: null,
        );
    }

    /**
     * Advance by the number of rows actually delivered, not by the echoed
     * `limit`. A page that returns fewer rows than its limit would otherwise
     * skip the difference, and an envelope that omits `limit` entirely would
     * stop the walk while `hasMore` still says there is more to fetch.
     *
     * An empty page terminates the walk: there is nothing to advance past, so
     * continuing would re-request the same offset forever.
     */
    public function next(): ?self
    {
        if (! $this->hasMore || $this->data === [] || ! $this->nextPageFetcher instanceof Closure) {
            return null;
        }

        return ($this->nextPageFetcher)($this->offset + count($this->data));
    }
}
