<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\DTO;

use OwnerPro\Asaas\Support\HasArrayFactory;

final readonly class BankAccount
{
    use HasArrayFactory;

    /** @param array<string, mixed>|Bank|null $bank */
    public function __construct(
        public string $ownerName,
        #[\SensitiveParameter]
        public string $cpfCnpj,
        public string $agency,
        #[\SensitiveParameter]
        public string $account,
        #[\SensitiveParameter]
        public string $accountDigit,
        public array|Bank|null $bank = null,
        public ?string $accountName = null,
        #[\SensitiveParameter]
        public ?string $ownerBirthDate = null,
        public ?string $bankAccountType = null,
        public ?string $ispb = null,
    ) {}

    /** @return array{ownerName: string, cpfCnpj: string, agency: string, account: string, accountDigit: string, bank: array<string, mixed>|Bank|null, accountName: ?string, ownerBirthDate: ?string, bankAccountType: ?string, ispb: ?string} */
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

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['ownerName', 'cpfCnpj', 'agency', 'account', 'accountDigit'];
    }
}
