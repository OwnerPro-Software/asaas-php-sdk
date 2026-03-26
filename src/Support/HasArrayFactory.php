<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

trait HasArrayFactory
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        foreach (static::requiredFields() as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException(sprintf("Field '%s' is required.", $field));
            }
        }

        $reflectionClass = new ReflectionClass(static::class);
        $constructor = $reflectionClass->getConstructor();

        if (! $constructor instanceof ReflectionMethod) {
            throw new InvalidArgumentException(sprintf("Class '%s' must have a constructor.", static::class));
        }

        $args = [];
        foreach ($constructor->getParameters() as $reflectionParameter) {
            $name = $reflectionParameter->getName();
            if (array_key_exists($name, $data)) {
                $args[] = $data[$name];
            } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                $args[] = $reflectionParameter->getDefaultValue();
            }
        }

        return $reflectionClass->newInstanceArgs($args);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $vars */
        $vars = get_object_vars($this);

        return array_filter($vars, fn (mixed $v): bool => $v !== null);
    }

    /** @return list<string> */
    abstract protected static function requiredFields(): array;
}
