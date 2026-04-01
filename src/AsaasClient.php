<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;
use SensitiveParameter;

final class AsaasClient
{
    private ?PaymentResource $paymentResource = null;

    private ?PixResource $pixResource = null;

    private ?PixTransactionResource $pixTransactionResource = null;

    private ?TransferResource $transferResource = null;

    private ?WebhookResource $webhookResource = null;

    private ?InvoiceResource $invoiceResource = null;

    private ?AccountResource $accountResource = null;

    private ?CreditCardResource $creditCardResource = null;

    private ?BillPaymentResource $billPaymentResource = null;

    private ?StatementResource $statementResource = null;

    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    public static function for(
        #[SensitiveParameter] string $apiKey,
        Environment|string $environment = Environment::Sandbox,
        int $timeout = 30,
    ): self {
        return new self(AsaasConnector::forStandalone($apiKey, $environment, $timeout));
    }

    public function payments(): PaymentResource
    {
        return $this->paymentResource ??= new PaymentResource($this->asaasConnector);
    }

    public function pix(): PixResource
    {
        return $this->pixResource ??= new PixResource($this->asaasConnector);
    }

    public function pixTransactions(): PixTransactionResource
    {
        return $this->pixTransactionResource ??= new PixTransactionResource($this->asaasConnector);
    }

    public function transfers(): TransferResource
    {
        return $this->transferResource ??= new TransferResource($this->asaasConnector);
    }

    public function webhooks(): WebhookResource
    {
        return $this->webhookResource ??= new WebhookResource($this->asaasConnector);
    }

    public function invoices(): InvoiceResource
    {
        return $this->invoiceResource ??= new InvoiceResource($this->asaasConnector);
    }

    public function accounts(): AccountResource
    {
        return $this->accountResource ??= new AccountResource($this->asaasConnector);
    }

    public function creditCards(): CreditCardResource
    {
        return $this->creditCardResource ??= new CreditCardResource($this->asaasConnector);
    }

    public function billPayments(): BillPaymentResource
    {
        return $this->billPaymentResource ??= new BillPaymentResource($this->asaasConnector);
    }

    public function statements(): StatementResource
    {
        return $this->statementResource ??= new StatementResource($this->asaasConnector);
    }
}
