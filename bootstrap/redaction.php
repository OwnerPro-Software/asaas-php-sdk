<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\Redactable;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

/**
 * Installs the VarDumper caster that keeps secrets out of `dump()`, `dd()` and
 * framework error pages.
 *
 * VarDumper reads `__debugInfo()` but *merges* it over the real property list,
 * so the raw API key, PAN, CVV and CPF/CNPJ stay visible next to their masked
 * twins. A caster replaces that list outright (`AbstractCloner::castObject()`),
 * which is why redaction has to be expressed this way rather than through the
 * magic method alone. It is keyed on {@see Redactable} — VarDumper walks a
 * class's interfaces when collecting casters — so one entry covers every
 * current and future implementor.
 *
 * **Why this lives in an autoloaded file rather than the service provider.**
 * `AbstractCloner::__construct()` copies `static::$defaultCasters` into the
 * instance, so a caster added after a cloner exists never reaches it. Laravel
 * builds the cloner behind `dump()`, `dd()` and the Ignition error page in
 * `FoundationServiceProvider::register()`, and every `register()` finishes
 * before any `boot()` begins — a provider is structurally too late. Composer
 * includes this file from `vendor/autoload.php`, before the framework
 * bootstraps at all, so the cloner Laravel builds already carries the caster.
 *
 * `AbstractCloner` is absent when `symfony/var-dumper` is not installed: a
 * plain-PHP host with no dumper has nothing to redact.
 */
if (class_exists(AbstractCloner::class)) {
    /**
     * VarDumper invokes casters with `($object, $properties, $stub, $isNested,
     * $filter)`; the four trailing arguments describe the property list being
     * replaced, so none of them can inform a redacted view. PHP passes them
     * harmlessly to a narrower userland signature.
     */
    AbstractCloner::$defaultCasters[Redactable::class] = static fn (Redactable $redactable): array => $redactable->__debugInfo();
}
