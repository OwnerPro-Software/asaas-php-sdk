<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

enum TransferRecurrenceFrequency: string
{
    case Weekly = 'WEEKLY';
    case Monthly = 'MONTHLY';
}
