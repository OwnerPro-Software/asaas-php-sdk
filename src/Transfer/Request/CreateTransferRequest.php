<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer\Request;

use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\HasArrayFactory;

final class CreateTransferRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|BankAccount|null $bankAccount */
    public function __construct(
        public readonly float $value,
        public readonly ?string $pixAddressKey = null,
        public readonly ?string $pixAddressKeyType = null,
        public readonly array|BankAccount|null $bankAccount = null,
        public readonly ?string $walletId = null,
        public readonly ?string $operationType = null,
        public readonly ?string $description = null,
        public readonly ?string $scheduleDate = null,
        public readonly ?string $externalReference = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if ($this->bankAccount instanceof BankAccount) {
            $data['bankAccount'] = $this->bankAccount->toArray();
        }

        return $data;
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['value'];
    }
}
