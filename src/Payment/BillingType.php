<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

enum BillingType: string
{
    case Undefined = 'UNDEFINED';
    case Boleto = 'BOLETO';
    case CreditCard = 'CREDIT_CARD';
    case DebitCard = 'DEBIT_CARD';
    case Transfer = 'TRANSFER';
    case Deposit = 'DEPOSIT';
    case Pix = 'PIX';
    case MundipaggCielo = 'MUNDIPAGG_CIELO';
    case VoucherCard = 'VOUCHER_CARD';
    case AsaasMoney = 'ASAAS_MONEY';
}
