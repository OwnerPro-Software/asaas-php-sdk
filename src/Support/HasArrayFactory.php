<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

use BackedEnum;

/** @see Arrayable */
trait HasArrayFactory
{
    abstract public static function fromArray(array $data): static;

    /**
     * @param  array<string, mixed>|static  $data
     * @return array<string, mixed>
     */
    public static function resolveData(array|self $data): array
    {
        $request = is_array($data) ? static::fromArray($data) : $data; // @phpstan-ignore argument.type

        return $request->toArray();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $vars */
        $vars = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        return $this->convertProperties($vars);
    }

    /**
     * @param  array<string, mixed>  $vars
     * @return array<string, mixed>
     */
    protected function convertProperties(array $vars): array
    {
        return array_map(function (mixed $value): mixed {
            if ($value instanceof BackedEnum) {
                return $value->value;
            }

            if ($value instanceof Arrayable) {
                return $value->toArray();
            }

            if (is_array($value)) {
                return array_map(
                    fn (mixed $item): mixed => match (true) {
                        $item instanceof BackedEnum => $item->value,
                        $item instanceof Arrayable => $item->toArray(),
                        default => $item,
                    },
                    $value,
                );
            }

            return $value;
        }, $vars);
    }
}
