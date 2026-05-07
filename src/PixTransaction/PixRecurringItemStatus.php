<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

enum PixRecurringItemStatus: string
{
    case Pending = 'PENDING';
    case Cancelled = 'CANCELLED';
    case Refused = 'REFUSED';
    case Done = 'DONE';
}
