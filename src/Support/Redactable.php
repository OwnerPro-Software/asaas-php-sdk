<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

/**
 * Marks an object whose `__debugInfo()` is the *only* representation safe to
 * show a developer — every other property is either a secret (API key, PAN,
 * CVV, CPF/CNPJ) or a path that transitively reaches one.
 *
 * `__debugInfo()` alone is not enough to enforce that. PHP's `var_dump()`
 * honours the hook exclusively, but Symfony VarDumper — which backs Laravel's
 * `dump()`, `dd()` and the Ignition/Flare error pages — *merges* the hook's
 * entries on top of the real property list instead of replacing it. This
 * interface is the registration key that `bootstrap/redaction.php` uses to
 * install a VarDumper caster, and a caster does replace the property list.
 *
 * Implement it on any new class that defines `__debugInfo()` for redaction
 * purposes; the caster then covers it with no further wiring.
 */
interface Redactable
{
    /** @return array<string, mixed> */
    public function __debugInfo(): array;
}
