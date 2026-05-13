<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\Arrayable;
use OwnerPro\Asaas\Support\BankAccountType;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class BankAccount implements Arrayable, JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public ?Bank $bank;

    /**
     * @param  array{code?: string}|Bank|null  $bank
     * @param  string|null  $ownerBirthDate  Format `YYYY-MM-DD`. Asaas rejects `T`/`Z`/timezone-suffixed strings with HTTP 400.
     */
    public function __construct(
        public string $ownerName,
        #[SensitiveParameter]
        public string $cpfCnpj,
        public string $agency,
        #[SensitiveParameter]
        public string $account,
        #[SensitiveParameter]
        public string $accountDigit,
        array|Bank|null $bank = null,
        public ?string $accountName = null,
        #[SensitiveParameter]
        public ?string $ownerBirthDate = null,
        public BankAccountType|string|null $bankAccountType = null,
        public ?string $ispb = null,
    ) {
        $this->bank = is_array($bank) ? Bank::fromArray($bank) : $bank;
    }

    /** @return array{ownerName: string, cpfCnpj: string, agency: string, account: string, accountDigit: string, bank: ?array<string, mixed>, accountName: ?string, ownerBirthDate: ?string, bankAccountType: BankAccountType|string|null, ispb: ?string} */
    public function __debugInfo(): array
    {
        return [
            'ownerName' => $this->ownerName,
            'cpfCnpj' => self::mask($this->cpfCnpj, 3),
            'agency' => $this->agency,
            'account' => self::mask($this->account, 2),
            'accountDigit' => '*',
            'bank' => $this->bank?->toArray(),
            'accountName' => $this->accountName,
            'ownerBirthDate' => $this->ownerBirthDate !== null ? '***' : null,
            'bankAccountType' => $this->bankAccountType,
            'ispb' => $this->ispb,
        ];
    }

    /** @param array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: BankAccountType|string, ispb?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            ownerName: $data['ownerName'] ?? throw new InvalidArgumentException('BankAccount: ownerName is required'),
            cpfCnpj: $data['cpfCnpj'] ?? throw new InvalidArgumentException('BankAccount: cpfCnpj is required'),
            agency: $data['agency'] ?? throw new InvalidArgumentException('BankAccount: agency is required'),
            account: $data['account'] ?? throw new InvalidArgumentException('BankAccount: account is required'),
            accountDigit: $data['accountDigit'] ?? throw new InvalidArgumentException('BankAccount: accountDigit is required'),
            bank: isset($data['bank']) ? Bank::fromArray($data['bank']) : null,
            accountName: $data['accountName'] ?? null,
            ownerBirthDate: $data['ownerBirthDate'] ?? null,
            bankAccountType: $data['bankAccountType'] ?? null,
            ispb: $data['ispb'] ?? null,
        );
    }
}
