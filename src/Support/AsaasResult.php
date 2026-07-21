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
     * The rows come back as loose maps rather than the `{code, description}`
     * shape `$errors` declares: this is a debug view, and the scrub answers a
     * map. Neither key is a credential name, so both survive — but that is a
     * fact about the key list, not something the type can carry.
     *
     * @return array{success: bool, data: ?array<string, mixed>, errors: ?list<array<string, mixed>>, response: ?RawResponse}
     */
    public function __debugInfo(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data === null ? null : SecretRedactor::scrub($this->data),
            // Two layers, because a row can carry a credential two ways.
            // `ErrorEnvelope` scrubs the body it pastes into a synthesized
            // `description`, which nothing here could recognise — that one is
            // free text, not a field. This scrubs a *field* named like a
            // credential on a row Asaas passed through untouched. Neither
            // covers the other.
            'errors' => $this->errors === null
                ? null
                : array_map(SecretRedactor::scrub(...), $this->errors),
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
     * @return array{success: bool, data: ?array<string, mixed>, errors: ?list<array<string, mixed>>, response: ?RawResponse}
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
