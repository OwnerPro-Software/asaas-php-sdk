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

final readonly class PaymentResource
{
    public function __construct(private Connector $connector) {}

    /** @param array<string, mixed>|CreatePaymentRequest $data */
    public function create(array|CreatePaymentRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/payments', CreatePaymentRequest::resolveData($data));
    }

    public function find(string $id): AsaasResult
    {
        return $this->connector->get('/v3/payments/'.$id, []);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/v3/payments', $query);
    }

    /** @param array<string, mixed>|UpdatePaymentRequest $data */
    public function update(string $id, array|UpdatePaymentRequest $data): AsaasResult
    {
        return $this->connector->put('/v3/payments/'.$id, UpdatePaymentRequest::resolveData($data));
    }

    public function delete(string $id): AsaasResult
    {
        return $this->connector->delete('/v3/payments/'.$id);
    }

    /** @param array<string, mixed>|RefundPaymentRequest $data */
    public function refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/payments/%s/refund', $id), RefundPaymentRequest::resolveData($data));
    }

    public function restore(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/payments/%s/restore', $id), []);
    }

    public function captureAuthorized(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/payments/%s/captureAuthorizedPayment', $id), []);
    }

    /** @param array<string, mixed>|PayWithCreditCardRequest $data */
    public function payWithCreditCard(string $id, array|PayWithCreditCardRequest $data): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/payments/%s/payWithCreditCard', $id), PayWithCreditCardRequest::resolveData($data));
    }

    /** @param array<string, mixed>|ReceivePaymentInCashRequest $data */
    public function receiveInCash(string $id, array|ReceivePaymentInCashRequest $data = []): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/payments/%s/receiveInCash', $id), ReceivePaymentInCashRequest::resolveData($data));
    }

    public function undoReceivedInCash(string $id): AsaasResult
    {
        return $this->connector->post(sprintf('/v3/payments/%s/undoReceivedInCash', $id), []);
    }

    public function status(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/payments/%s/status', $id), []);
    }

    public function billingInfo(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/payments/%s/billingInfo', $id), []);
    }

    public function pixQrCode(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/payments/%s/pixQrCode', $id), []);
    }

    public function identificationField(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/payments/%s/identificationField', $id), []);
    }

    public function viewingInfo(string $id): AsaasResult
    {
        return $this->connector->get(sprintf('/v3/payments/%s/viewingInfo', $id), []);
    }

    /** @param array<string, mixed>|SimulatePaymentRequest $data */
    public function simulate(array|SimulatePaymentRequest $data): AsaasResult
    {
        return $this->connector->post('/v3/payments/simulate', SimulatePaymentRequest::resolveData($data));
    }

    public function limits(): AsaasResult
    {
        return $this->connector->get('/v3/payments/limits', []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function all(array $filters = []): Generator
    {
        return $this->connector->all('/v3/payments', $filters);
    }
}
