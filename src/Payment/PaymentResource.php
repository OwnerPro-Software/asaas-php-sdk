<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use Generator;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\DeletedDTO;

final class PaymentResource
{
    public function __construct(private readonly AsaasConnector $asaasConnector) {}

    /** @param array<string, mixed>|CreatePaymentData $data */
    public function create(array|CreatePaymentData $data): AsaasResult
    {
        $dto = $data instanceof CreatePaymentData ? $data : CreatePaymentData::fromArray($data);

        return $this->asaasConnector->post('/v3/payments', $dto->toArray(), PaymentDTO::class);
    }

    public function find(string $id): AsaasResult
    {
        return $this->asaasConnector->get('/v3/payments/'.$id, [], PaymentDTO::class);
    }

    /** @param array<string, mixed> $query */
    public function list(array $query = []): AsaasPaginatedResult
    {
        return $this->asaasConnector->paginate('/v3/payments', $query, PaymentDTO::class);
    }

    /** @param array<string, mixed>|UpdatePaymentData $data */
    public function update(string $id, array|UpdatePaymentData $data): AsaasResult
    {
        $dto = $data instanceof UpdatePaymentData ? $data : UpdatePaymentData::fromArray($data);

        return $this->asaasConnector->put('/v3/payments/'.$id, $dto->toArray(), PaymentDTO::class);
    }

    public function delete(string $id): AsaasResult
    {
        return $this->asaasConnector->delete('/v3/payments/'.$id, DeletedDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function refund(string $id, array $data = []): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/refund', $id), $data, PaymentDTO::class);
    }

    public function restore(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/restore', $id), [], PaymentDTO::class);
    }

    public function captureAuthorized(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/captureAuthorizedPayment', $id), [], PaymentDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function payWithCreditCard(string $id, array $data): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/payWithCreditCard', $id), $data, PaymentDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function receiveInCash(string $id, array $data = []): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/receiveInCash', $id), $data, PaymentDTO::class);
    }

    public function undoReceivedInCash(string $id): AsaasResult
    {
        return $this->asaasConnector->post(sprintf('/v3/payments/%s/undoReceivedInCash', $id), [], PaymentDTO::class);
    }

    public function status(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/status', $id), [], PaymentStatusDTO::class);
    }

    public function billingInfo(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/billingInfo', $id), [], BillingInfoDTO::class);
    }

    public function pixQrCode(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/pixQrCode', $id), [], PixQrCodeDTO::class);
    }

    public function identificationField(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/identificationField', $id), [], IdentificationFieldDTO::class);
    }

    public function viewingInfo(string $id): AsaasResult
    {
        return $this->asaasConnector->get(sprintf('/v3/payments/%s/viewingInfo', $id), [], ViewingInfoDTO::class);
    }

    /** @param array<string, mixed> $data */
    public function simulate(array $data): AsaasResult
    {
        return $this->asaasConnector->post('/v3/payments/simulate', $data, PaymentSimulationDTO::class);
    }

    public function limits(): AsaasResult
    {
        return $this->asaasConnector->get('/v3/payments/limits', [], PaymentLimitsDTO::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, PaymentDTO>
     */
    public function all(array $filters = []): Generator
    {
        return $this->asaasConnector->all('/v3/payments', $filters, PaymentDTO::class);
    }
}
