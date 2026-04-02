<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Invoice;

enum InvoiceStatus: string
{
    case Scheduled = 'SCHEDULED';
    case Authorized = 'AUTHORIZED';
    case ProcessingCancellation = 'PROCESSING_CANCELLATION';
    case Canceled = 'CANCELED';
    case CancellationDenied = 'CANCELLATION_DENIED';
    case Error = 'ERROR';
}
