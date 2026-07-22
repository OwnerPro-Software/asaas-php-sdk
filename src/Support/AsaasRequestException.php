<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;

final class AsaasRequestException extends AsaasException implements JsonSerializable, Redactable
{
    public readonly int $statusCode;

    /** @param list<array{code?: string, description?: string}> $errors */
    public function __construct(
        public readonly array $errors,
        public readonly ?RawResponse $response,
    ) {
        $this->statusCode = $this->response?->status() ?? 0;
        parent::__construct($this->describe($errors[0]['description'] ?? null), $this->statusCode);
    }

    /**
     * This carries the same rows {@see AsaasResult} and
     * {@see AsaasPaginatedError} scrub, by the same routes: `dump($e)` and the
     * Ignition error page through the {@see Redactable} caster, and
     * `Log::error('failed', ['e' => $e])` through `jsonSerialize()`. A rejected
     * `POST /accounts` answers with a row carrying the subaccount's live
     * `apiKey`, and `orFail()` is what turns that row into this object — the
     * one representation of it that was still printing the key beside the
     * `***` its own `$response` showed for the same bytes.
     *
     * The exception's own diagnostics are restated because the caster
     * *replaces* the property list rather than adding to it: without them,
     * redacting the rows would cost the reader the message, file and line that
     * are the point of dumping an exception.
     *
     * @return array{message: string, statusCode: int, errors: list<array<string, mixed>>, response: ?RawResponse, file: string, line: int}
     */
    public function __debugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'statusCode' => $this->statusCode,
            'errors' => array_map(SecretRedactor::scrub(...), $this->errors),
            'response' => $this->response,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * @return array{message: string, statusCode: int, errors: list<array<string, mixed>>, response: ?RawResponse, file: string, line: int}
     */
    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }

    /**
     * `ErrorEnvelope` passes a canonical row through verbatim, so `description`
     * is whatever Asaas — or a proxy impersonating it — put there. An empty
     * string would leave the log line with nothing to act on, and a non-string
     * would raise `TypeError` out of `Exception::__construct()`, escaping the
     * Result-based contract this exception exists to serve.
     */
    private function describe(mixed $description): string
    {
        return is_string($description) && $description !== ''
            ? $description
            : 'Asaas API error';
    }
}
