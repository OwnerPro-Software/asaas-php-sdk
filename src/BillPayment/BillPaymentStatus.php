<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\BillPayment;

enum BillPaymentStatus: string
{
    case Pending = 'PENDING';
    case BankProcessing = 'BANK_PROCESSING';
    case Paid = 'PAID';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';
    case Refunded = 'REFUNDED';
    case AwaitingCheckoutRiskAnalysisRequest = 'AWAITING_CHECKOUT_RISK_ANALYSIS_REQUEST';
}
