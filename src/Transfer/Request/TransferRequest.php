<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Pix\PixAddressKeyType;
use OwnerPro\Asaas\Support\BankAccountType;
use OwnerPro\Asaas\Support\DTO\BankAccount;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use OwnerPro\Asaas\Transfer\TransferOperationType;
use SensitiveParameter;

final readonly class TransferRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public ?BankAccount $bankAccount;

    /** @param array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: BankAccountType|string, ispb?: string}|BankAccount|null $bankAccount */
    public function __construct(
        public float $value,
        #[SensitiveParameter]
        public ?string $pixAddressKey = null,
        public PixAddressKeyType|string|null $pixAddressKeyType = null,
        #[SensitiveParameter]
        array|BankAccount|null $bankAccount = null,
        public ?string $walletId = null,
        public TransferOperationType|string|null $operationType = null,
        public ?string $description = null,
        public ?string $scheduleDate = null,
        public ?string $externalReference = null,
    ) {
        $this->bankAccount = is_array($bankAccount) ? BankAccount::fromArray($bankAccount) : $bankAccount;
    }

    /** @return array{value: float, pixAddressKey: ?string, pixAddressKeyType: PixAddressKeyType|string|null, bankAccount: ?array<string, mixed>, walletId: ?string, operationType: TransferOperationType|string|null, description: ?string, scheduleDate: ?string, externalReference: ?string} */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'pixAddressKey' => $this->pixAddressKey !== null ? self::mask($this->pixAddressKey, 4) : null,
            'pixAddressKeyType' => $this->pixAddressKeyType,
            'bankAccount' => $this->bankAccount?->__debugInfo(),
            'walletId' => $this->walletId,
            'operationType' => $this->operationType,
            'description' => $this->description,
            'scheduleDate' => $this->scheduleDate,
            'externalReference' => $this->externalReference,
        ];
    }

    /** @param array{value?: float, pixAddressKey?: string, pixAddressKeyType?: PixAddressKeyType|string, bankAccount?: array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: string, ispb?: string}, walletId?: string, operationType?: TransferOperationType|string, description?: string, scheduleDate?: string, externalReference?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('TransferRequest: value is required'),
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
