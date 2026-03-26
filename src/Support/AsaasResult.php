<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final class AsaasResult
{
    /**
     * @param  ?list<array{code?: string, description?: string}>  $errors
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?BaseResponse $data,
        public readonly ?array $errors,
        public readonly int $statusCode,
    ) {}

    public static function success(BaseResponse $baseResponse, int $statusCode): self
    {
        return new self(
            success: true,
            data: $baseResponse,
            errors: null,
            statusCode: $statusCode,
        );
    }

    /** @param list<array{code?: string, description?: string}> $errors */
    public static function failure(array $errors, int $statusCode): self
    {
        return new self(
            success: false,
            data: null,
            errors: $errors,
            statusCode: $statusCode,
        );
    }

    public function throw(): self
    {
        if (! $this->success) {
            throw new AsaasRequestException($this->errors ?? [], $this->statusCode);
        }

        return $this;
    }
}
