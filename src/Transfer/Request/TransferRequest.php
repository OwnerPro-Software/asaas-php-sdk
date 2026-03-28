<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer\Request;

use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class TransferRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|BankAccount|null $bankAccount */
    public function __construct(
        public float $value,
        public ?string $pixAddressKey = null,
        public ?string $pixAddressKeyType = null,
        public array|BankAccount|null $bankAccount = null,
        public ?string $walletId = null,
        public ?string $operationType = null,
        public ?string $description = null,
        public ?string $scheduleDate = null,
        public ?string $externalReference = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['value'];
    }
}
