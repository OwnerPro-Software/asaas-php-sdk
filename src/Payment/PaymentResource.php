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

    public function find(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s', self::BASE, IdGuard::validate($id)));
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE, $query);
    }

    /** @param array<string, mixed>|UpdatePaymentRequest $data */
    public function update(string $id, array|UpdatePaymentRequest $data): AsaasResult
    {
        return $this->connector->put(sprintf('%s/%s', self::BASE, IdGuard::validate($id)), UpdatePaymentRequest::resolveData($data));
    }

    public function delete(string $id): AsaasResult
    {
        return $this->connector->delete(sprintf('%s/%s', self::BASE, IdGuard::validate($id)));
    }

    /** @param array<string, mixed>|RefundPaymentRequest $data */
    public function refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/refund', self::BASE, IdGuard::validate($id)), RefundPaymentRequest::resolveData($data));
    }

    public function restore(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/restore', self::BASE, IdGuard::validate($id)));
    }

    public function captureAuthorized(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/captureAuthorizedPayment', self::BASE, IdGuard::validate($id)));
    }

    /** @param array<string, mixed>|PayWithCreditCardRequest $data */
    public function payWithCreditCard(string $id, array|PayWithCreditCardRequest $data): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/payWithCreditCard', self::BASE, IdGuard::validate($id)), PayWithCreditCardRequest::resolveData($data));
    }

    /** @param array<string, mixed>|ReceivePaymentInCashRequest $data */
    public function receiveInCash(string $id, array|ReceivePaymentInCashRequest $data = []): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/receiveInCash', self::BASE, IdGuard::validate($id)), ReceivePaymentInCashRequest::resolveData($data));
    }

    public function undoReceivedInCash(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('%s/%s/undoReceivedInCash', self::BASE, IdGuard::validate($id)));
    }

    public function status(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s/status', self::BASE, IdGuard::validate($id)));
    }

    public function billingInfo(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s/billingInfo', self::BASE, IdGuard::validate($id)));
    }

    public function pixQrCode(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s/pixQrCode', self::BASE, IdGuard::validate($id)));
    }

    public function identificationField(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s/identificationField', self::BASE, IdGuard::validate($id)));
    }

    public function viewingInfo(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('%s/%s/viewingInfo', self::BASE, IdGuard::validate($id)));
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
}
