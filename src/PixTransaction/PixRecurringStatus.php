<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

enum PixRecurringStatus: string
{
    case AwaitingCriticalActionAuthorization = 'AWAITING_CRITICAL_ACTION_AUTHORIZATION';
    case Pending = 'PENDING';
    case Scheduled = 'SCHEDULED';
    case Cancelled = 'CANCELLED';
    case Done = 'DONE';
}
