<?php

declare(strict_types=1);

namespace OwnerPro\Asaas;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ResponseSequence;
use OwnerPro\Asaas\Account\AccountResource;
use OwnerPro\Asaas\Account\MyAccountResource;
use OwnerPro\Asaas\BillPayment\BillPaymentResource;
use OwnerPro\Asaas\Contracts\AsaasClientContract;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\FiscalInfo\FiscalInfoResource;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Payment\LeanPaymentResource;
use OwnerPro\Asaas\Payment\PaymentResource;
use OwnerPro\Asaas\Pix\PixResource;
use OwnerPro\Asaas\PixAutomatic\PixAutomaticResource;
use OwnerPro\Asaas\PixTransaction\PixTransactionResource;
use OwnerPro\Asaas\Statement\StatementResource;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\DumpRedaction;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Support\Redactable;
use OwnerPro\Asaas\Testing\FakeAsaasClient;
use OwnerPro\Asaas\Transfer\TransferResource;
use OwnerPro\Asaas\Webhook\WebhookResource;
use SensitiveParameter;

final class AsaasClient implements AsaasClientContract, Redactable
{
    /** @var array<class-string, object> */
    private array $resources = [];

    public function __construct(private readonly Connector $connector) {}

    /** @return array{resources: list<string>} */
    public function __debugInfo(): array
    {
        return [
            'resources' => [
                'payments',
                'pix',
                'pixTransactions',
                'pixAutomatic',
                'transfers',
                'webhooks',
                'invoices',
                'accounts',
                'myAccount',
                'creditCards',
                'billPayments',
                'statements',
                'fiscalInfo',
                'leanPayments',
            ],
        ];
    }

    public static function for(
        #[SensitiveParameter] string $apiKey,
        Environment|string $environment = Environment::Sandbox,
        int $timeout = 30,
        int $connectTimeout = 10,
        bool $throwOnTransportFailure = false,
    ): self {
        // The standalone entry point has no service provider to boot, so it is
        // where the dump caster gets installed for plain-PHP hosts.
        DumpRedaction::register();

        return new self(AsaasConnector::forStandalone($apiKey, $environment, $timeout, $connectTimeout, $throwOnTransportFailure));
    }

    /** @param array<string, array<string, mixed>|PromiseInterface|ResponseSequence|Closure> $stubs */
    public static function fake(array $stubs = [], Environment|string $environment = Environment::Sandbox, bool $throwOnTransportFailure = false): FakeAsaasClient
    {
        return new FakeAsaasClient($stubs, $environment, $throwOnTransportFailure);
    }

    public function payments(): PaymentResource
    {
        return $this->resolve(PaymentResource::class);
    }

    public function pix(): PixResource
    {
        return $this->resolve(PixResource::class);
    }

    public function pixTransactions(): PixTransactionResource
    {
        return $this->resolve(PixTransactionResource::class);
    }

    public function pixAutomatic(): PixAutomaticResource
    {
        return $this->resolve(PixAutomaticResource::class);
    }

    public function transfers(): TransferResource
    {
        return $this->resolve(TransferResource::class);
    }

    public function webhooks(): WebhookResource
    {
        return $this->resolve(WebhookResource::class);
    }

    public function invoices(): InvoiceResource
    {
        return $this->resolve(InvoiceResource::class);
    }

    public function accounts(): AccountResource
    {
        return $this->resolve(AccountResource::class);
    }

    public function myAccount(): MyAccountResource
    {
        return $this->resolve(MyAccountResource::class);
    }

    public function creditCards(): CreditCardResource
    {
        return $this->resolve(CreditCardResource::class);
    }

    public function billPayments(): BillPaymentResource
    {
        return $this->resolve(BillPaymentResource::class);
    }

    public function statements(): StatementResource
    {
        return $this->resolve(StatementResource::class);
    }

    public function fiscalInfo(): FiscalInfoResource
    {
        return $this->resolve(FiscalInfoResource::class);
    }

    public function leanPayments(): LeanPaymentResource
    {
        return $this->resolve(LeanPaymentResource::class);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $resource
     * @return T
     */
    private function resolve(string $resource): object
    {
        /** @var T */
        return $this->resources[$resource] ??= new $resource($this->connector);
    }
}
