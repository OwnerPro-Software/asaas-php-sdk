<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use InvalidArgumentException;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class DeleteAccountRequest
{
    use HasArrayFactory;

    public function __construct(public string $removeReason)
    {
        if ($removeReason === '') {
            throw new InvalidArgumentException('DeleteAccountRequest: removeReason must not be empty.');
        }
    }

    /** @param array{removeReason?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            removeReason: $data['removeReason'] ?? throw new InvalidArgumentException('DeleteAccountRequest: removeReason is required'),
        );
    }
}
