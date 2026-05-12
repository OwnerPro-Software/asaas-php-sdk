<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Contracts;

use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\Account\MyAccountResource;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\FiscalInfo\FiscalInfoResource;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Payment\LeanPaymentResource;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticResource;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;

interface AsaasClientContract
{
    public function payments(): PaymentResource;

    public function pix(): PixResource;

    public function pixTransactions(): PixTransactionResource;

    public function pixAutomatic(): PixAutomaticResource;

    public function transfers(): TransferResource;

    public function webhooks(): WebhookResource;

    public function invoices(): InvoiceResource;

    public function accounts(): AccountResource;

    public function myAccount(): MyAccountResource;

    public function creditCards(): CreditCardResource;

    public function billPayments(): BillPaymentResource;

    public function statements(): StatementResource;

    public function fiscalInfo(): FiscalInfoResource;

    public function leanPayments(): LeanPaymentResource;
}
