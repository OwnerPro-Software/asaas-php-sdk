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

    /**
     * @param  string  $successUrl  maxLength: 255 (validated server-side by Asaas across every endpoint that accepts a `callback`).
     */
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

    /** @param array{successUrl?: string, autoRedirect?: bool}|Callback|Missing $value */
    public static function coerce(array|self|Missing $value): self|Missing
    {
        return is_array($value) ? self::fromArray($value) : $value;
    }
}
