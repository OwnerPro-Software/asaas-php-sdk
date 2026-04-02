<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

enum PixTransactionType: string
{
    case Debit = 'DEBIT';
    case Credit = 'CREDIT';
    case CreditRefund = 'CREDIT_REFUND';
    case DebitRefund = 'DEBIT_REFUND';
    case DebitRefundCancellation = 'DEBIT_REFUND_CANCELLATION';
}
