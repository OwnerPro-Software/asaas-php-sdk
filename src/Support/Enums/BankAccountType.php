<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\Enums;

enum BankAccountType: string
{
    case CheckingAccount = 'CONTA_CORRENTE';
    case SavingsAccount = 'CONTA_POUPANCA';
}
