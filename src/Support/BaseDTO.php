<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use LogicException;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseDTO
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private array $attributes)
    {
        foreach ((new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            unset($this->{$reflectionProperty->getName()});
        }
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function __set(string $name, mixed $value): void
    {
        throw new LogicException(sprintf("Cannot modify property '%s' on an immutable DTO.", $name));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
