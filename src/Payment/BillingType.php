<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

/**
 * BillingType — covers both the **request** input (what Asaas accepts on
 * `POST /payments`) and the **response** output (how Asaas reports the billing
 * channel that ultimately settled the payment).
 *
 * **Request-acceptable** (the only values valid on create/update bodies):
 *   - `Undefined`, `Boleto`, `CreditCard`, `Pix`
 *
 * **Response-only** (returned by Asaas to describe how the payment was received;
 * passing these on a request body produces HTTP 400):
 *   - `DebitCard`, `Transfer`, `Deposit`, `MundipaggCielo`, `VoucherCard`, `AsaasMoney`
 */
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
