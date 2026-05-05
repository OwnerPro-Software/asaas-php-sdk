<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account\Request;

use InvalidArgumentException;
use JsonSerializable;
use OwnerPro\Asaas\Support\BankAccountType;
use OwnerPro\Asaas\Support\HasArrayFactory;
use OwnerPro\Asaas\Support\MasksSensitiveData;
use SensitiveParameter;

final readonly class AccountBankAccountRequest implements JsonSerializable
{
    use HasArrayFactory;
    use MasksSensitiveData;

    public function __construct(
        public string $bankCode,
        public string $agency,
        #[SensitiveParameter]
        public string $account,
        #[SensitiveParameter]
        public string $accountDigit,
        public BankAccountType|string $accountType,
        public ?string $pixAddressKey = null,
        public ?string $pixAddressKeyType = null,
    ) {}

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'bankCode' => $this->bankCode,
            'agency' => $this->agency,
            'account' => self::mask($this->account, 2),
            'accountDigit' => '*',
            'accountType' => $this->accountType,
            'pixAddressKey' => $this->pixAddressKey,
            'pixAddressKeyType' => $this->pixAddressKeyType,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $accountType = $this->accountType instanceof BankAccountType
            ? $this->accountType->value
            : $this->accountType;

        $payload = [
            'bank' => ['code' => $this->bankCode],
            'agency' => $this->agency,
            'account' => $this->account,
            'accountDigit' => $this->accountDigit,
            'bankAccountType' => $accountType,
        ];

        if ($this->pixAddressKey !== null) {
            $payload['pixAddressKey'] = $this->pixAddressKey;
        }

        if ($this->pixAddressKeyType !== null) {
            $payload['pixAddressKeyType'] = $this->pixAddressKeyType;
        }

        return $payload;
    }

    /** @param array{bankCode?: string, agency?: string, account?: string, accountDigit?: string, accountType?: BankAccountType|string, pixAddressKey?: string|null, pixAddressKeyType?: string|null} $data */
    public static function fromArray(array $data): static
    {
        return new self(
            bankCode: $data['bankCode'] ?? throw new InvalidArgumentException('AccountBankAccountRequest: bankCode is required'),
            agency: $data['agency'] ?? throw new InvalidArgumentException('AccountBankAccountRequest: agency is required'),
            account: $data['account'] ?? throw new InvalidArgumentException('AccountBankAccountRequest: account is required'),
            accountDigit: $data['accountDigit'] ?? throw new InvalidArgumentException('AccountBankAccountRequest: accountDigit is required'),
            accountType: $data['accountType'] ?? throw new InvalidArgumentException('AccountBankAccountRequest: accountType is required'),
            pixAddressKey: $data['pixAddressKey'] ?? null,
            pixAddressKeyType: $data['pixAddressKeyType'] ?? null,
        );
    }
}
