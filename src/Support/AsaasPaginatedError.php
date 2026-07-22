<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;

final readonly class AsaasPaginatedError implements JsonSerializable, Redactable
{
    /**
     * @param  list<array{code?: string, description?: string}>  $errors
     * @param  bool  $relayed  whether the rows came from Asaas (a failed page)
     *                         or were written by this SDK (a `PAGINATION_*`
     *                         fault); it is what {@see self::orFail()} throws on
     */
    private function __construct(
        public array $errors,
        public ?RawResponse $response,
        public int $offset,
        public int $limit,
        private bool $relayed,
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
     * A page Asaas itself refused. The rows are its envelope, verbatim.
     *
     * @param  list<array{code?: string, description?: string}>  $errors
     */
    public static function fromApi(array $errors, ?RawResponse $rawResponse, int $offset, int $limit): self
    {
        return new self($errors, $rawResponse, $offset, $limit, relayed: true);
    }

    /**
     * A fault this SDK diagnosed while walking: the pages were answered, they
     * just did not add up. See {@see AsaasPaginationException}.
     *
     * @param  list<array{code?: string, description?: string}>  $errors
     */
    public static function fromWalk(array $errors, ?RawResponse $rawResponse, int $offset, int $limit): self
    {
        return new self($errors, $rawResponse, $offset, $limit, relayed: false);
    }

    /**
     * @return array{errors: list<array<string, mixed>>, response: ?RawResponse, offset: int, limit: int}
     */
    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }

    /**
     * Only a page Asaas refused carries a verdict, and only a verdict may
     * surface as an `AsaasRequestException` — the type whose `statusCode` the
     * caller is entitled to read as one. A `PAGINATION_*` fault has no verdict
     * behind it, so it gets its own type instead of an exception reporting a
     * status nobody stated.
     */
    public function orFail(): never
    {
        throw $this->relayed
            ? new AsaasRequestException($this->errors, $this->response)
            : new AsaasPaginationException($this->errors, $this->response, $this->offset, $this->limit);
    }
}
