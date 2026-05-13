<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Partial-update payload factory.
 *
 * `toArray()` filters out `Missing::Value` — and **only** `Missing::Value`. DTO
 * properties consuming this trait MUST be typed as `T|Missing` (never
 * `T|Missing|null`): `Missing::Value` represents "omit from the body", while
 * an explicit `null` would be sent literally as `{"field": null}` — something
 * the Asaas spec never accepts (every request-body field is `nullable: false`
 * — confirmed by `grep` across all 33 domain spec files).
 *
 * Pairing `T|Missing|null` with this trait was the root cause of the v2.1
 * null-leak bug fixed in `UpdateInvoiceRequest`, `UpdatePaymentRequest`, and
 * `UpdateWebhookRequest`. Stick to `T|Missing` for new partial-update DTOs.
 */
trait HasUpdatableArrayFactory
{
    use HasArrayFactory;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $vars */
        $vars = array_filter(get_object_vars($this), fn (mixed $v): bool => ! $v instanceof Missing);

        return $this->convertProperties($vars);
    }
}
