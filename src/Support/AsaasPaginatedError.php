<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final readonly class AsaasPaginatedError
{
    /**
     * @param  list<array{code?: string, description?: string}>  $errors
     */
    public function __construct(
        public array $errors,
        public ?RawResponse $response,
        public int $offset,
        public int $limit,
    ) {}

    public function orFail(): never
    {
        throw new AsaasRequestException($this->errors, $this->response);
    }
}
