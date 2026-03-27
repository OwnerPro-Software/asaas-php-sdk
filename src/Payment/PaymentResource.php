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
use OwnerPro\Asaas\Payment\Response\BillingInfoResponse;
use OwnerPro\Asaas\Payment\Response\IdentificationFieldResponse;
use OwnerPro\Asaas\Payment\Response\PaymentLimitsResponse;
use OwnerPro\Asaas\Payment\Response\PaymentResponse;
use OwnerPro\Asaas\Payment\Response\PaymentSimulationResponse;
use OwnerPro\Asaas\Payment\Response\PaymentStatusResponse;
use OwnerPro\Asaas\Payment\Response\PixQrCodeResponse;
use OwnerPro\Asaas\Payment\Response\ViewingInfoResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedResponse;

final class PaymentResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreatePaymentRequest $data */
    public function create(array|CreatePaymentRequest $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/payments', CreatePaymentRequest::resolveData($data), PaymentResponse::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/payments/'.$id, [], PaymentResponse::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/payments', $query, PaymentResponse::class);
    }

    /** @param array<string, mixed>|UpdatePaymentRequest $data */
    public function update(string $id, array|UpdatePaymentRequest $data): AsaasResult
    {
        return $this->asaasConnector->put('/v3/payments/'.$id, UpdatePaymentRequest::resolveData($data), PaymentResponse::class);
    }

    public function delete(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/payments/'.$id, DeletedResponse::class);
    }

    /** @param array<string, mixed>|RefundPaymentRequest $data */
    public function refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/refund', $id), RefundPaymentRequest::resolveData($data), PaymentResponse::class);
    }

    public function restore(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/restore', $id), [], PaymentResponse::class);
    }

    public function captureAuthorized(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/captureAuthorizedPayment', $id), [], PaymentResponse::class);
    }

    /** @param array<string, mixed>|PayWithCreditCardRequest $data */
    public function payWithCreditCard(string $id, array|PayWithCreditCardRequest $data): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/payWithCreditCard', $id), PayWithCreditCardRequest::resolveData($data), PaymentResponse::class);
    }

    /** @param array<string, mixed>|ReceivePaymentInCashRequest $data */
    public function receiveInCash(string $id, array|ReceivePaymentInCashRequest $data = []): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/receiveInCash', $id), ReceivePaymentInCashRequest::resolveData($data), PaymentResponse::class);
    }

    public function undoReceivedInCash(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/undoReceivedInCash', $id), [], PaymentResponse::class);
    }

    public function status(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/status', $id), [], PaymentStatusResponse::class);
    }

    public function billingInfo(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/billingInfo', $id), [], BillingInfoResponse::class);
    }

    public function pixQrCode(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/pixQrCode', $id), [], PixQrCodeResponse::class);
    }

    public function identificationField(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/identificationField', $id), [], IdentificationFieldResponse::class);
    }

    public function viewingInfo(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/viewingInfo', $id), [], ViewingInfoResponse::class);
    }

    /** @param array<string, mixed>|SimulatePaymentRequest $data */
    public function simulate(array|SimulatePaymentRequest $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/payments/simulate', SimulatePaymentRequest::resolveData($data), PaymentSimulationResponse::class);
    }

    public function limits(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/payments/limits', [], PaymentLimitsResponse::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PaymentResponse>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/payments', $filters, PaymentResponse::class);
    }
}
