<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\Missing;

final readonly class Callback implements Arrayable
{
    use HasArrayFactory;

    public function __construct(
        public string $successUrl,
        public ?bool $autoRedirect = null,
    ) {}

    /** @param array{successUrl?: string, autoRedirect?: bool} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            successUrl: $data['successUrl'] ?? throw new InvalidArgumentException('Callback: successUrl is required'),
            autoRedirect: $data['autoRedirect'] ?? null,
        );
    }

    /** @param array{successUrl?: string, autoRedirect?: bool}|Callback|Missing|null $value */
    public static function coerce(array|self|Missing|null $value): self|Missing|null
    {
        return is_array($value) ? self::fromArray($value) : $value;
    }
}
