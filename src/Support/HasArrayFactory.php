<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use BackedEnum;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

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
                $args[] = self::hydrateParameter($data[$name], $reflectionParameter);
            } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                $args[] = $reflectionParameter->getDefaultValue();
            }
        }

        return $reflectionClass->newInstanceArgs($args);
    }

    /**
     * @param  array<string, mixed>|static  $data
     * @return array<string, mixed>
     */
    public static function resolveData(array|self $data): array
    {
        $request = is_array($data) ? static::fromArray($data) : $data;

        return $request->toArray();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $vars */
        $vars = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        return array_map(function (mixed $value): mixed {
            if ($value instanceof BackedEnum) {
                return $value->value;
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            if (is_array($value)) {
                return array_map(
                    fn (mixed $item): mixed => match (true) {
                        $item instanceof BackedEnum => $item->value,
                        is_object($item) && method_exists($item, 'toArray') => $item->toArray(),
                        default => $item,
                    },
                    $value,
                );
            }

            return $value;
        }, $vars);
    }

    /** @return list<string> */
    abstract protected static function requiredFields(): array;

    private static function hydrateParameter(mixed $value, ReflectionParameter $reflectionParameter): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $type = $reflectionParameter->getType();

        if (! $type instanceof ReflectionUnionType) {
            return $value;
        }

        $dtoClass = self::findDtoClass($type);

        if ($dtoClass === null) {
            return $value;
        }

        /** @var array<string, mixed> $arrayValue */
        $arrayValue = $value;

        return $dtoClass::fromArray($arrayValue);
    }

    private static function findDtoClass(ReflectionUnionType $reflectionUnionType): ?string
    {
        foreach ($reflectionUnionType->getTypes() as $namedType) {
            /** @var ReflectionNamedType $namedType */
            if (! $namedType->isBuiltin() && method_exists($namedType->getName(), 'fromArray')) {
                return $namedType->getName();
            }
        }

        return null;
    }
}
