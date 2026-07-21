<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use JsonSerializable;
use stdClass;

/**
 * Keeps an empty request body encoded as a JSON **object**.
 *
 * PHP models both JSON shapes with one type, and `json_encode([])` picks the
 * array: a DTO whose fields are all optional — `CancelInvoiceRequest`, every
 * `Update*Request`, `RefundPaymentRequest` — would ship `[]` where Asaas
 * declares an object schema.
 *
 * The shape is decided at serialization time rather than by handing a bare
 * `stdClass` to the HTTP client, whose body contract accepts arrays and
 * `JsonSerializable` but not arbitrary objects.
 *
 * @internal Encoding detail of {@see AsaasConnector}.
 */
final readonly class JsonBody implements JsonSerializable
{
    /** @param array<string, mixed> $data */
    private function __construct(private array $data) {}

    /** @param array<string, mixed> $data */
    public static function of(array $data): self
    {
        return new self($data);
    }

    /** @return array<string, mixed>|stdClass */
    public function jsonSerialize(): array|stdClass
    {
        return $this->data === [] ? new stdClass : $this->data;
    }
}
