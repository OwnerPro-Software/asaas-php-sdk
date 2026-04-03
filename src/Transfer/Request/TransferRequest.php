<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer\Request;

use OwnerPro\Asaas\Pix\PixAddressKeyType;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Transfer\TransferOperationType;
use TypeError;

final readonly class TransferRequest
{
    use HasArrayFactory;

    /** @param array<string, mixed>|BankAccount|null $bankAccount */
    public function __construct(
        public float $value,
        public ?string $pixAddressKey = null,
        public PixAddressKeyType|string|null $pixAddressKeyType = null,
        public array|BankAccount|null $bankAccount = null,
        public ?string $walletId = null,
        public TransferOperationType|string|null $operationType = null,
        public ?string $description = null,
        public ?string $scheduleDate = null,
        public ?string $externalReference = null,
    ) {}

    /** @param array{value?: float, pixAddressKey?: string, pixAddressKeyType?: PixAddressKeyType|string, bankAccount?: array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: string, ispb?: string}, walletId?: string, operationType?: TransferOperationType|string, description?: string, scheduleDate?: string, externalReference?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new TypeError('value is required'),
            pixAddressKey: $data['pixAddressKey'] ?? null,
            pixAddressKeyType: $data['pixAddressKeyType'] ?? null,
            bankAccount: isset($data['bankAccount']) ? BankAccount::fromArray($data['bankAccount']) : null,
            walletId: $data['walletId'] ?? null,
            operationType: $data['operationType'] ?? null,
            description: $data['description'] ?? null,
            scheduleDate: $data['scheduleDate'] ?? null,
            externalReference: $data['externalReference'] ?? null,
        );
    }
}
