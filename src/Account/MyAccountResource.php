<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use OwnerPro\Asaas\Account\Request\AccountBankAccountRequest;
use OwnerPro\Asaas\Account\Request\CommercialInfoRequest;
use OwnerPro\Asaas\Account\Request\DeleteAccountRequest;
use OwnerPro\Asaas\Account\Request\PaymentCheckoutConfigRequest;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Connector;
use OwnerPro\Asaas\Support\IdGuard;

final readonly class MyAccountResource
{
    private const string BASE = '/myAccount';

    public function __construct(private Connector $connector) {}

    public function status(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/status');
    }

    /**
     * `POST /v3/sandbox/myAccount/approve` — sandbox-only helper that
     * fast-approves every status slot (commercial info, bank account,
     * documentation, general). Returns HTTP 400 in production; only call
     * against `Environment::Sandbox`. No request body.
     */
    public function approveSandbox(): AsaasResult
    {
        return $this->connector->post('/sandbox/myAccount/approve');
    }

    public function accountNumber(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/accountNumber');
    }

    public function fees(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/fees/');
    }

    public function commercialInfo(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/commercialInfo');
    }

    /** @param array<string, mixed>|CommercialInfoRequest $data */
    public function updateCommercialInfo(array|CommercialInfoRequest $data): AsaasResult
    {
        return $this->connector->post(self::BASE.'/commercialInfo', CommercialInfoRequest::resolveData($data));
    }

    public function documents(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/documents');
    }

    /** @param string|resource $file Document file contents (binary). */
    public function uploadDocumentFile(
        string $documentId,
        mixed $file,
        DocumentType|string $type,
        string $filename,
    ): AsaasResult {
        $typeValue = $type instanceof DocumentType ? $type->value : $type;

        return $this->connector->postMultipart(
            $this->documentFilesPath($documentId),
            ['type' => $typeValue],
            [[
                'name' => 'documentFile',
                'contents' => $file,
                'filename' => $filename,
            ]],
        );
    }

    public function findDocumentFile(string $fileId): AsaasResult
    {
        return $this->connector->get($this->documentFilePath($fileId));
    }

    /** @param string|resource $file Document file contents (binary). */
    public function updateDocumentFile(
        string $fileId,
        mixed $file,
        DocumentType|string $type,
        string $filename,
    ): AsaasResult {
        $typeValue = $type instanceof DocumentType ? $type->value : $type;

        return $this->connector->postMultipart(
            $this->documentFilePath($fileId),
            ['type' => $typeValue],
            [[
                'name' => 'documentFile',
                'contents' => $file,
                'filename' => $filename,
            ]],
        );
    }

    public function deleteDocumentFile(string $fileId): AsaasResult
    {
        return $this->connector->delete($this->documentFilePath($fileId));
    }

    /**
     * `GET /v3/myAccount/bankAccountInfo`.
     *
     * Not formally documented in the `my-account` OpenAPI spec but accepted by
     * Asaas in practice. Used by the subaccount onboarding flow to read the
     * configured withdrawal account before `updateBankAccount()`.
     */
    public function bankAccount(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/bankAccountInfo');
    }

    /**
     * `POST /v3/myAccount/bankAccountInfo`. Same spec-coverage caveat as
     * `bankAccount()`: undocumented in the spec, supported in production.
     *
     * @param  array<string, mixed>|AccountBankAccountRequest  $data
     */
    public function updateBankAccount(array|AccountBankAccountRequest $data): AsaasResult
    {
        return $this->connector->post(
            self::BASE.'/bankAccountInfo',
            AccountBankAccountRequest::resolveData($data),
        );
    }

    /**
     * Asaas accepts `removeReason` as a query parameter on `DELETE /myAccount`. The
     * `Connector::delete()` contract has no query overload (no other DELETE endpoint
     * needs one today), so the value is appended to the path here.
     *
     * @param  array{removeReason?: string}|DeleteAccountRequest  $data
     */
    public function delete(array|DeleteAccountRequest $data): AsaasResult
    {
        $request = $data instanceof DeleteAccountRequest ? $data : DeleteAccountRequest::fromArray($data);

        return $this->connector->delete(self::BASE.'?removeReason='.rawurlencode($request->removeReason));
    }

    public function paymentCheckoutConfig(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/paymentCheckoutConfig/');
    }

    /**
     * @param  array<string, mixed>|PaymentCheckoutConfigRequest  $data
     * @param  string|resource|null  $logoFile  Optional logo image (binary).
     */
    public function updatePaymentCheckoutConfig(
        array|PaymentCheckoutConfigRequest $data,
        mixed $logoFile = null,
        ?string $logoFilename = null,
    ): AsaasResult {
        $files = [];

        if ($logoFile !== null) {
            $files[] = [
                'name' => 'logoFile',
                'contents' => $logoFile,
                'filename' => $logoFilename ?? 'logo.png',
            ];
        }

        return $this->connector->postMultipart(
            self::BASE.'/paymentCheckoutConfig/',
            PaymentCheckoutConfigRequest::resolveData($data),
            $files,
        );
    }

    /** @param array<string, mixed> $query */
    public function wallets(array $query = []): AsaasPaginatedResult
    {
        return $this->connector->paginate('/wallets/', $query);
    }

    private function documentFilesPath(string $documentId): string
    {
        return sprintf('%s/documents/%s/files', self::BASE, IdGuard::validate($documentId));
    }

    private function documentFilePath(string $fileId): string
    {
        return sprintf('%s/documents/files/%s', self::BASE, IdGuard::validate($fileId));
    }
}
