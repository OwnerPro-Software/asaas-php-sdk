<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Sentinel used by partial-update DTOs (see `HasUpdatableArrayFactory`) to
 * distinguish "omit from the wire" from any legitimate scalar value. The
 * paired pattern is `public T|Missing $field = Missing::Value` — never
 * `T|Missing|null`, because the Asaas spec marks every request-body field as
 * `nullable: false` and would reject `{"field": null}`.
 */
enum Missing
{
    case Value;
}
