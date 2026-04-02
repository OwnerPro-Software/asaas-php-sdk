<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixTransaction;

enum PixTransactionStatus: string
{
    case AwaitingBalanceValidation = 'AWAITING_BALANCE_VALIDATION';
    case AwaitingInstantPaymentAccountBalance = 'AWAITING_INSTANT_PAYMENT_ACCOUNT_BALANCE';
    case AwaitingCriticalActionAuthorization = 'AWAITING_CRITICAL_ACTION_AUTHORIZATION';
    case AwaitingCheckoutRiskAnalysisRequest = 'AWAITING_CHECKOUT_RISK_ANALYSIS_REQUEST';
    case AwaitingCashInRiskAnalysisRequest = 'AWAITING_CASH_IN_RISK_ANALYSIS_REQUEST';
    case Scheduled = 'SCHEDULED';
    case AwaitingRequest = 'AWAITING_REQUEST';
    case Requested = 'REQUESTED';
    case Done = 'DONE';
    case Refused = 'REFUSED';
    case Cancelled = 'CANCELLED';
}
