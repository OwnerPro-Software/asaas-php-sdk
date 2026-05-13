<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice\Request;

use OwnerPro\Asaas\Support\HasUpdatableArrayFactory;
use OwnerPro\Asaas\Support\Missing;

/**
 * Body for `POST /v3/invoices/{id}/cancel`.
 *
 * `cancelOnlyOnAsaas` is optional in the spec; the server-side default is
 * undocumented (omitted = full cancellation, including in the prefeitura).
 * Use `Missing` so the SDK never sends the flag unless the caller opts in,
 * preserving whatever default Asaas decides upstream.
 *
 * The `Cancel` prefix is kept (instead of bare `InvoiceRequest`) to avoid
 * semantic conflict with the entity verb `cancel`.
 */
final readonly class CancelInvoiceRequest
{
    use HasUpdatableArrayFactory;

    public function __construct(
        public bool|Missing $cancelOnlyOnAsaas = Missing::Value,
    ) {}

    /** @param array{cancelOnlyOnAsaas?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            cancelOnlyOnAsaas: $data['cancelOnlyOnAsaas'] ?? Missing::Value,
        );
    }
}
