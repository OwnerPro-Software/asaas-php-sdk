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
use OwnerPro\Asaas\Support\Redactable;
use OwnerPro\Asaas\Transfer\TransferOperationType;
use OwnerPro\Asaas\Transfer\TransferRecurrenceFrequency;
use SensitiveParameter;

final readonly class TransferRequest implements JsonSerializable, Redactable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public ?BankAccount $bankAccount;

    public ?Recurring $recurring;

    /**
     * @param  array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: BankAccountType|string, ispb?: string}|BankAccount|null  $bankAccount
     * @param  array{frequency?: TransferRecurrenceFrequency|string, quantity?: int}|Recurring|null  $recurring
     * @param  string|null  $scheduleDate  Format `YYYY-MM-DD`.
     */
    public function __construct(
        public float $value,
        #[SensitiveParameter]
        public ?string $pixAddressKey = null,
        public PixAddressKeyType|string|null $pixAddressKeyType = null,
        #[SensitiveParameter]
        array|BankAccount|null $bankAccount = null,
        public TransferOperationType|string|null $operationType = null,
        public ?string $description = null,
        public ?string $scheduleDate = null,
        public ?string $externalReference = null,
        array|Recurring|null $recurring = null,
    ) {
        $this->bankAccount = is_array($bankAccount) ? BankAccount::fromArray($bankAccount) : $bankAccount;
        $this->recurring = is_array($recurring) ? Recurring::fromArray($recurring) : $recurring;
    }

    /** @return array{value: float, pixAddressKey: ?string, pixAddressKeyType: PixAddressKeyType|string|null, bankAccount: ?array<string, mixed>, operationType: TransferOperationType|string|null, description: ?string, scheduleDate: ?string, externalReference: ?string, recurring: ?Recurring} */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'pixAddressKey' => $this->pixAddressKey !== null ? self::mask($this->pixAddressKey, 4) : null,
            'pixAddressKeyType' => $this->pixAddressKeyType,
            'bankAccount' => $this->bankAccount?->__debugInfo(),
            'operationType' => $this->operationType,
            'description' => $this->description,
            'scheduleDate' => $this->scheduleDate,
            'externalReference' => $this->externalReference,
            'recurring' => $this->recurring,
        ];
    }

    /** @param array{value?: float, pixAddressKey?: string, pixAddressKeyType?: PixAddressKeyType|string, bankAccount?: array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: string, ispb?: string}, operationType?: TransferOperationType|string, description?: string, scheduleDate?: string, externalReference?: string, recurring?: array{frequency?: TransferRecurrenceFrequency|string, quantity?: int}|Recurring} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            value: $data['value'] ?? throw new InvalidArgumentException('TransferRequest: value is required'),
            pixAddressKey: $data['pixAddressKey'] ?? null,
            pixAddressKeyType: $data['pixAddressKeyType'] ?? null,
            bankAccount: $data['bankAccount'] ?? null,
            operationType: $data['operationType'] ?? null,
            description: $data['description'] ?? null,
            scheduleDate: $data['scheduleDate'] ?? null,
            externalReference: $data['externalReference'] ?? null,
            recurring: $data['recurring'] ?? null,
        );
    }
}
