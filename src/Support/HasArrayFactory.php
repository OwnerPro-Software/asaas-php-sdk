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
                return self::reindexList(array_map(
                    fn (mixed $item): mixed => match (true) {
                        $item instanceof BackedEnum => $item->value,
                        $item instanceof Arrayable => $item->toArray(),
                        default => $item,
                    },
                    $value,
                ));
            }

            return $value;
        }, $vars);
    }

    /**
     * Keeps an integer-keyed array encoding as a JSON **array**.
     *
     * `array_filter()` over a list — the idiomatic way to drop entries before
     * handing them to the SDK — leaves the surviving keys in place, and
     * `json_encode()` renders the resulting gap as an object. Asaas declares
     * `split`, `splitRefunds`, `permissions`, `events` and `billingTypes` as
     * `"type": "array"` and answers a 400 the caller cannot diagnose.
     *
     * String-keyed maps are returned untouched: there the key *is* payload.
     *
     * @param  array<array-key, mixed>  $items
     * @return array<array-key, mixed>
     */
    private static function reindexList(array $items): array
    {
        foreach (array_keys($items) as $key) {
            if (! is_int($key)) {
                return $items;
            }
        }

        return array_values($items);
    }
}
