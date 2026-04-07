<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use JsonSerializable;
use OwnerPro\Asaas\Support\Enums\BankAccountType;
use OwnerPro\Asaas\Support\HasArrayFactory;
use SensitiveParameter;
use TypeError;

final readonly class BankAccount implements JsonSerializable
{
    use HasArrayFactory;

    /** @param array<string, mixed>|Bank|null $bank */
    public function __construct(
        public string $ownerName,
        #[SensitiveParameter]
        public string $cpfCnpj,
        public string $agency,
        #[SensitiveParameter]
        public string $account,
        #[SensitiveParameter]
        public string $accountDigit,
        public array|Bank|null $bank = null,
        public ?string $accountName = null,
        #[SensitiveParameter]
        public ?string $ownerBirthDate = null,
        public BankAccountType|string|null $bankAccountType = null,
        public ?string $ispb = null,
    ) {}

    /** @return array{ownerName: string, cpfCnpj: string, agency: string, account: string, accountDigit: string, bank: array<string, mixed>|Bank|null, accountName: ?string, ownerBirthDate: ?string, bankAccountType: BankAccountType|string|null, ispb: ?string} */
    public function __debugInfo(): array
    {
        return [
            'ownerName' => $this->ownerName,
            'cpfCnpj' => str_repeat('*', max(0, strlen($this->cpfCnpj) - 3)).substr($this->cpfCnpj, -3),
            'agency' => $this->agency,
            'account' => str_repeat('*', max(0, strlen($this->account) - 2)).substr($this->account, -2),
            'accountDigit' => '*',
            'bank' => $this->bank,
            'accountName' => $this->accountName,
            'ownerBirthDate' => $this->ownerBirthDate !== null ? '***' : null,
            'bankAccountType' => $this->bankAccountType,
            'ispb' => $this->ispb,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->__debugInfo();
    }

    /** @param array{ownerName?: string, cpfCnpj?: string, agency?: string, account?: string, accountDigit?: string, bank?: array{code?: string}, accountName?: string, ownerBirthDate?: string, bankAccountType?: BankAccountType|string, ispb?: string} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            ownerName: $data['ownerName'] ?? throw new TypeError('ownerName is required'),
            cpfCnpj: $data['cpfCnpj'] ?? throw new TypeError('cpfCnpj is required'),
            agency: $data['agency'] ?? throw new TypeError('agency is required'),
            account: $data['account'] ?? throw new TypeError('account is required'),
            accountDigit: $data['accountDigit'] ?? throw new TypeError('accountDigit is required'),
            bank: isset($data['bank']) ? Bank::fromArray($data['bank']) : null,
            accountName: $data['accountName'] ?? null,
            ownerBirthDate: $data['ownerBirthDate'] ?? null,
            bankAccountType: $data['bankAccountType'] ?? null,
            ispb: $data['ispb'] ?? null,
        );
    }
}
