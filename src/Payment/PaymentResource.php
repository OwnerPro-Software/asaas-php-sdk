<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use Generator;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest;
use OwnerPro\Asaas\Payment\Request\ReceivePaymentInCashRequest;
use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Payment\Request\SimulatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class PaymentResource
{
    private const string BASE = '/payments';

    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreatePaymentRequest $data */
    public function create(array|CreatePaymentRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE, CreatePaymentRequest::resolveData($data));
    }

    /** @param array<string, mixed>|CreatePaymentRequest $data */
    public function createWithCreditCard(array|CreatePaymentRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE.'/', CreatePaymentRequest::resolveData($data));
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE, $query);
    }

    /** @param array<string, mixed>|UpdatePaymentRequest $data */
    public function update(string $id, array|UpdatePaymentRequest $data): AsaasResult
    {
        return $this->connector->put($this->path($id), UpdatePaymentRequest::resolveData($data));
    }

    public function delete(string $id): AsaasResult
    {
        return $this->connector->delete($this->path($id));
    }

    /** @param array<string, mixed>|RefundPaymentRequest $data */
    public function refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
    {
        return $this->connector->post($this->path($id, '/refund'), RefundPaymentRequest::resolveData($data));
    }

    public function listRefunds(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/refunds'));
    }

    public function refundBankSlip(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/bankSlip/refund'));
    }

    public function getChargeback(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/chargeback'));
    }

    public function getEscrow(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/escrow'));
    }

    public function restore(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/restore'));
    }

    public function captureAuthorized(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/captureAuthorizedPayment'));
    }

    /** @param array<string, mixed>|PayWithCreditCardRequest $data */
    public function payWithCreditCard(string $id, array|PayWithCreditCardRequest $data): AsaasResult
    {
        return $this->connector->post($this->path($id, '/payWithCreditCard'), PayWithCreditCardRequest::resolveData($data));
    }

    /** @param array<string, mixed>|ReceivePaymentInCashRequest $data */
    public function receiveInCash(string $id, array|ReceivePaymentInCashRequest $data = []): AsaasResult
    {
        return $this->connector->post($this->path($id, '/receiveInCash'), ReceivePaymentInCashRequest::resolveData($data));
    }

    public function undoReceivedInCash(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/undoReceivedInCash'));
    }

    public function status(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/status'));
    }

    public function billingInfo(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/billingInfo'));
    }

    public function pixQrCode(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/pixQrCode'));
    }

    public function identificationField(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/identificationField'));
    }

    public function viewingInfo(string $id): AsaasResult
    {
        return $this->connector->get($this->path($id, '/viewingInfo'));
    }

    /** @param array<string, mixed>|SimulatePaymentRequest $data */
    public function simulate(array|SimulatePaymentRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE.'/simulate', SimulatePaymentRequest::resolveData($data));
    }

    public function limits(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/limits');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all(self::BASE, $filters);
    }

    private function path(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::BASE, IdGuard::validate($id), $suffix);
    }
}
