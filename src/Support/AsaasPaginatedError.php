<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;

final readonly class AsaasPaginatedError implements JsonSerializable, Redactable
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

    /**
     * This is what `all()` hands the caller in place of a page, so it reaches a
     * log by exactly the routes `AsaasResult` and `AsaasPaginatedResult` guard:
     * `dump($error)` through the VarDumper caster, and
     * `Log::info('walk', ['err' => $error])` through `jsonSerialize()`.
     * `$response` is {@see Redactable} in its own right; `$errors` is not, and
     * a row Asaas passed through untouched can carry a credential in a field
     * named like one. See {@see AsaasResult::__debugInfo()} for why the scrub
     * here and the one `ErrorEnvelope` already applied cover different halves.
     *
     * @return array{errors: list<array<string, mixed>>, response: ?RawResponse, offset: int, limit: int}
     */
    public function __debugInfo(): array
    {
        return [
            'errors' => array_map(SecretRedactor::scrub(...), $this->errors),
            'response' => $this->response,
            'offset' => $this->offset,
            'limit' => $this->limit,
        ];
    }

    /**
     * @return array{errors: list<array<string, mixed>>, response: ?RawResponse, offset: int, limit: int}
     */
    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }

    public function orFail(): never
    {
        throw new AsaasRequestException($this->errors, $this->response);
    }
}
