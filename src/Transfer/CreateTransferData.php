<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateTransferData
{
    use HasArrayFactory;

    /** @param array<string, mixed>|null $bankAccount */
    public function __construct(
        public readonly float $value,
        public readonly ?string $pixAddressKey = null,
        public readonly ?string $pixAddressKeyType = null,
        public readonly ?array $bankAccount = null,
        public readonly ?string $walletId = null,
        public readonly ?string $operationType = null,
        public readonly ?string $description = null,
        public readonly ?string $scheduleDate = null,
        public readonly ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['value'];
    }
}
