<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Support\Enums;

enum BankAccountType: string
{
    case ContaCorrente = 'CONTA_CORRENTE';
    case ContaPoupanca = 'CONTA_POUPANCA';
}
