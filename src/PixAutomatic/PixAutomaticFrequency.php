<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixAutomatic;

enum PixAutomaticFrequency: string
{
    case Weekly = 'WEEKLY';
    case Monthly = 'MONTHLY';
    case Quarterly = 'QUARTERLY';
    case Semiannually = 'SEMIANNUALLY';
    case Annually = 'ANNUALLY';
}
