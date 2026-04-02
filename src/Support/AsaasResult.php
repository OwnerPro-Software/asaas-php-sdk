<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final readonly class AsaasResult
{
    use ThrowsOnFailure;

    /**
     * @param  ?list<array{code?: string, description?: string}>  $errors
     */
    public function __construct(
        public bool $success,
        public ?BaseResponse $data,
        public ?array $errors,
        public ?RawResponse $response,
    ) {}

    public static function success(BaseResponse $baseResponse, RawResponse $rawResponse): self
    {
        return new self(
            success: true,
            data: $baseResponse,
            errors: null,
            response: $rawResponse,
        );
    }

    /** @param list<array{code?: string, description?: string}> $errors */
    public static function failure(array $errors, ?RawResponse $rawResponse = null): self
    {
        return new self(
            success: false,
            data: null,
            errors: $errors,
            response: $rawResponse,
        );
    }
}
