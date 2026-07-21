<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use Symfony\Component\VarDumper\Cloner\AbstractCloner;

/**
 * Installs the Symfony VarDumper caster that keeps secrets out of `dump()`,
 * `dd()` and framework error pages.
 *
 * VarDumper reads `__debugInfo()` but *merges* it over the real property list,
 * so the raw API key, PAN, CVV and CPF/CNPJ stay visible. A caster replaces
 * that list outright (`AbstractCloner::castObject()`), which is why redaction
 * has to be expressed this way rather than through the magic method alone.
 *
 * Registration is keyed on the {@see Redactable} interface — VarDumper walks a
 * class's parents *and* interfaces when collecting casters — so every current
 * and future implementor is covered by this single entry.
 *
 * The registration is a plain assignment and therefore idempotent: calling
 * `register()` from several entry points cannot stack duplicate casters.
 */
final class DumpRedaction
{
    /**
     * Called by `AsaasServiceProvider::boot()` and by `AsaasClient::for()`, so
     * both the Laravel and the standalone entry point are covered without the
     * consumer knowing this class exists.
     *
     * `AbstractCloner` is absent when `symfony/var-dumper` is not installed —
     * a plain-PHP host with no dumper has nothing to redact.
     */
    public static function register(): void
    {
        if (! class_exists(AbstractCloner::class)) {
            return; // @codeCoverageIgnore
        }

        AbstractCloner::$defaultCasters[Redactable::class] = self::cast(...);
    }

    /**
     * Discards the property list VarDumper collected and answers with the
     * object's own redacted view.
     *
     * VarDumper invokes casters with `($object, $properties, $stub, $isNested,
     * $filter)`; the four trailing arguments describe the property list being
     * replaced, so none of them can inform a redacted view. PHP passes them
     * harmlessly to a narrower userland signature.
     *
     * @return array<string, mixed>
     */
    public static function cast(Redactable $redactable): array
    {
        return $redactable->__debugInfo();
    }
}
