<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

final readonly class AsaasResult
{
    use ThrowsOnFailure;

    /**
     * @param  ?array<string, mixed>  $data
     * @param  ?list<array{code?: string, description?: string}>  $errors
     */
    public function __construct(
        public bool $success,
        public ?array $data,
        public ?array $errors,
        public ?RawResponse $response,
    ) {}

    /** @param array<string, mixed> $data */
    public static function success(array $data, RawResponse $rawResponse): self
    {
        return new self(
            success: true,
            data: $data,
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
