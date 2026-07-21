<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

use Generator;
use OwnerPro\Asaas\Payment\Request\CreatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\PayWithCreditCardRequest;
use OwnerPro\Asaas\Payment\Request\ReceivePaymentInCashRequest;
use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Payment\Request\SimulatePaymentRequest;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentDocumentRequest;
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
        return $this->connector->post(
            self::BASE.'/',
            CreatePaymentRequest::ensureReadyForCreditCard($data)->toArray(),
        );
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

    /** @param array<string, mixed> $query */
    public function listRefunds(string $id, array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate($this->path($id, '/refunds'), $query);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>|AsaasPaginatedError>
     */
    public function allRefunds(string $id, array $filters = []): Generator
    {
        return $this->connector->all($this->path($id, '/refunds'), $filters);
    }

    /**
     * Starts the customer-driven bank-slip refund flow. The endpoint takes no
     * body — it is not the credit-card refund in another shape — and answers
     * with a single `requestUrl`, the link the payer opens to submit bank
     * details and documents. There is no partial-refund amount to send.
     */
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

    /**
     * @param  string  $escrowId  the escrow **guarantee** id — not the payment id.
     *                            Read it from `getEscrow($paymentId)`, whose response
     *                            `id` field carries it (a UUID, e.g.
     *                            `4f468235-cec3-482f-b3d0-348af4c7194`).
     */
    public function finishEscrow(string $escrowId): AsaasResult
    {
        return $this->connector->post($this->escrowPath($escrowId));
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

    /** @param array<string, mixed> $query */
    public function listSplitsPaid(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/splits/paid', $query);
    }

    public function findSplitPaid(string $id): AsaasResult
    {
        return $this->connector->get($this->splitPath('paid', $id));
    }

    /** @param array<string, mixed> $query */
    public function listSplitsReceived(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate(self::BASE.'/splits/received', $query);
    }

    public function findSplitReceived(string $id): AsaasResult
    {
        return $this->connector->get($this->splitPath('received', $id));
    }

    /** @param string|resource $file */
    public function uploadDocument(
        string $paymentId,
        mixed $file,
        PaymentDocumentType|string $type,
        bool $availableAfterPayment,
        string $filename,
    ): AsaasResult {
        $typeValue = $type instanceof PaymentDocumentType ? $type->value : $type;

        return $this->connector->postMultipart(
            $this->path($paymentId, '/documents'),
            [
                'type' => $typeValue,
                'availableAfterPayment' => $availableAfterPayment,
            ],
            [[
                'name' => 'file',
                'contents' => $file,
                'filename' => $filename,
            ]],
        );
    }

    /** @param array<string, mixed> $query */
    public function listDocuments(string $paymentId, array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate($this->path($paymentId, '/documents'), $query);
    }

    public function findDocument(string $paymentId, string $documentId): AsaasResult
    {
        return $this->connector->get($this->documentPath($paymentId, $documentId));
    }

    /** @param array<string, mixed>|UpdatePaymentDocumentRequest $data */
    public function updateDocument(string $paymentId, string $documentId, array|UpdatePaymentDocumentRequest $data): AsaasResult
    {
        return $this->connector->put(
            $this->documentPath($paymentId, $documentId),
            UpdatePaymentDocumentRequest::resolveData($data),
        );
    }

    public function deleteDocument(string $paymentId, string $documentId): AsaasResult
    {
        return $this->connector->delete($this->documentPath($paymentId, $documentId));
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

    private function documentPath(string $paymentId, string $documentId): string
    {
        return sprintf('%s/%s/documents/%s', self::BASE, IdGuard::validate($paymentId), IdGuard::validate($documentId));
    }

    private function splitPath(string $kind, string $id): string
    {
        return sprintf('%s/splits/%s/%s', self::BASE, $kind, IdGuard::validate($id));
    }

    private function escrowPath(string $escrowId): string
    {
        return sprintf('/escrow/%s/finish', IdGuard::validate($escrowId));
    }
}
