<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

enum PaymentStatus: string
{
    case Pending = 'PENDING';
    case Received = 'RECEIVED';
    case Confirmed = 'CONFIRMED';
    case Overdue = 'OVERDUE';
    case Refunded = 'REFUNDED';
    case ReceivedInCash = 'RECEIVED_IN_CASH';
    case RefundRequested = 'REFUND_REQUESTED';
    case RefundInProgress = 'REFUND_IN_PROGRESS';
    case ChargebackRequested = 'CHARGEBACK_REQUESTED';
    case ChargebackDispute = 'CHARGEBACK_DISPUTE';
    case AwaitingChargebackReversal = 'AWAITING_CHARGEBACK_REVERSAL';
    case DunningRequested = 'DUNNING_REQUESTED';
    case DunningReceived = 'DUNNING_RECEIVED';
    case AwaitingRiskAnalysis = 'AWAITING_RISK_ANALYSIS';
}
