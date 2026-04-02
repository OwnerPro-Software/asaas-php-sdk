<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Transfer;

enum TransferStatus: string
{
    case Pending = 'PENDING';
    case BankProcessing = 'BANK_PROCESSING';
    case Done = 'DONE';
    case Cancelled = 'CANCELLED';
    case Failed = 'FAILED';
}
