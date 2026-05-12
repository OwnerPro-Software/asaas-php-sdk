<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

enum AccessTokenPermission: string
{
    case Customer = 'CUSTOMER';
    case CustomerNotification = 'CUSTOMER_NOTIFICATION';
    case Payment = 'PAYMENT';
    case PaymentRefund = 'PAYMENT_REFUND';
    case Chargeback = 'CHARGEBACK';
    case Installment = 'INSTALLMENT';
    case Subscription = 'SUBSCRIPTION';
    case PixCredit = 'PIX_CREDIT';
    case PaymentLink = 'PAYMENT_LINK';
    case Checkout = 'CHECKOUT';
    case CreditCard = 'CREDIT_CARD';
    case Anticipation = 'ANTICIPATION';
    case AnticipationConfig = 'ANTICIPATION_CONFIG';
    case Escrow = 'ESCROW';
    case EscrowConfig = 'ESCROW_CONFIG';
    case CreditBureau = 'CREDIT_BUREAU';
    case PaymentDunning = 'PAYMENT_DUNNING';
    case FiscalInfo = 'FISCAL_INFO';
    case Invoice = 'INVOICE';
    case PixDebit = 'PIX_DEBIT';
    case PixAddressKey = 'PIX_ADDRESS_KEY';
    case PixRecurring = 'PIX_RECURRING';
    case PixTransaction = 'PIX_TRANSACTION';
    case PixAutomatic = 'PIX_AUTOMATIC';
    case Transfer = 'TRANSFER';
    case Bill = 'BILL';
    case MobilePhoneRecharge = 'MOBILE_PHONE_RECHARGE';
    case FinancialTransaction = 'FINANCIAL_TRANSACTION';
    case AccountInfo = 'ACCOUNT_INFO';
    case PaymentCheckoutConfig = 'PAYMENT_CHECKOUT_CONFIG';
    case Webhook = 'WEBHOOK';
    case SubAccount = 'SUB_ACCOUNT';
    case AccountDocument = 'ACCOUNT_DOCUMENT';
}
