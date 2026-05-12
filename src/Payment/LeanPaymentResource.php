<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\ReceivePaymentInCashRequest;
use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class LeanPaymentResource
{
    private const string BASE = '/lean/payments';

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

    public function captureAuthorized(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/captureAuthorizedPayment'));
    }

    public function restore(string $id): AsaasResult
    {
        return $this->connector->post($this->path($id, '/restore'));
    }

    /** @param array<string, mixed>|RefundPaymentRequest $data */
    public function refund(string $id, array|RefundPaymentRequest $data = []): AsaasResult
    {
        return $this->connector->post($this->path($id, '/refund'), RefundPaymentRequest::resolveData($data));
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

    private function path(string $id, string $suffix = ''): string
    {
        return sprintf('%s/%s%s', self::BASE, IdGuard::validate($id), $suffix);
    }
}
