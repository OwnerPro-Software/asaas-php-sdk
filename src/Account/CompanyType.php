<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

enum CompanyType: string
{
    case Mei = 'MEI';
    case Limited = 'LIMITED';
    case Individual = 'INDIVIDUAL';
    case Association = 'ASSOCIATION';
}
