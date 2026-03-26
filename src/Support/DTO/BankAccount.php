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
        public string $cpfCnpj,
        public string $agency,
        public string $account,
        public string $accountDigit,
        public array|Bank|null $bank = null,
        public ?string $accountName = null,
        public ?string $ownerBirthDate = null,
        public ?string $bankAccountType = null,
        public ?string $ispb = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $data */
        $data = array_filter(get_object_vars($this), fn (mixed $v): bool => $v !== null);

        if ($this->bank instanceof Bank) {
            $data['bank'] = $this->bank->toArray();
        }

        return $data;
    }

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['ownerName', 'cpfCnpj', 'agency', 'account', 'accountDigit'];
    }
}
