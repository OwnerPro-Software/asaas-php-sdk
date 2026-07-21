<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;

final readonly class AsaasResult implements JsonSerializable, Redactable
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

    /**
     * `$data` is the response body verbatim, so the credential-bearing
     * endpoints — `POST /accounts`, the `accessTokens` pair, `GET /webhooks`,
     * card tokenization — put a live secret on a public property. Printing the
     * result while debugging would otherwise disclose it.
     *
     * `$response` stays an object: it is {@see Redactable} in its own right and
     * scrubs its body when the dumper reaches it.
     *
     * @return array{success: bool, data: ?array<string, mixed>, errors: ?list<array{code?: string, description?: string}>, response: ?RawResponse}
     */
    public function __debugInfo(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data === null ? null : SecretRedactor::scrub($this->data),
            'errors' => $this->errors,
            'response' => $this->response,
        ];
    }

    /**
     * The dump surface is not the only one that reaches a log file.
     * `Log::info('created', ['result' => $result])` hands the result to
     * Monolog, which `json_encode()`s its context — without this hook the
     * encoder walks the public properties and writes the live `apiKey` of a
     * freshly created subaccount into the log.
     *
     * @return array{success: bool, data: ?array<string, mixed>, errors: ?list<array{code?: string, description?: string}>, response: ?RawResponse}
     */
    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }

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
