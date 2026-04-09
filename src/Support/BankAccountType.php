<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support;

enum BankAccountType: string
{
    case CheckingAccount = 'CONTA_CORRENTE';
    case SavingsAccount = 'CONTA_POUPANCA';
}
