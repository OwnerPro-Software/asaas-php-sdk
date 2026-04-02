<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

abstract class BaseResponse
{
    /** @var array<string, class-string> */
    private array $dtoTypes = [];

    /** @var array<string, mixed> */
    private array $hydrated = [];

    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
        foreach ((new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            unset($this->{$reflectionProperty->getName()});
            $this->registerPropertyType($reflectionProperty);
        }
    }

    public function __get(string $name): mixed
    {
        $value = $this->attributes[$name] ?? null;

        if (! is_array($value) || ! isset($this->dtoTypes[$name])) {
            return $value;
        }

        return $this->hydrated[$name] ??= $this->dtoTypes[$name]::fromArray($value);
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function __set(string $name, mixed $value): void
    {
        throw new LogicException(sprintf("Cannot modify property '%s' on an immutable response.", $name));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    private function registerPropertyType(ReflectionProperty $reflectionProperty): void
    {
        $type = $reflectionProperty->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return;
        }

        /** @var class-string $className */
        $className = $type->getName();

        if (method_exists($className, 'fromArray')) {
            $this->dtoTypes[$reflectionProperty->getName()] = $className;
        }
    }
}
