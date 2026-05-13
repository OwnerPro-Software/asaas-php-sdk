<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

/**
 * Operation rail used to move funds out of an Asaas account.
 *
 * **Request-acceptable** (the only values valid on `POST /v3/transfers` bodies):
 *  - `Pix`, `Ted`
 *
 * **Response-only** (returned by Asaas to describe how the transfer was routed;
 * passing it on a request body produces HTTP 400):
 *  - `Internal` — set when the transfer was made between Asaas accounts via
 *    `POST /v3/transfers/` (the dedicated internal-transfer endpoint, handled
 *    by `TransferResource::createInternal()` / `InternalTransferRequest`).
 *
 * Kept as a single enum so consumers can `match` over the raw string returned
 * in `$result->data['operationType']` without maintaining a separate map.
 */
enum TransferOperationType: string
{
    case Pix = 'PIX';
    case Ted = 'TED';
    case Internal = 'INTERNAL';
}
