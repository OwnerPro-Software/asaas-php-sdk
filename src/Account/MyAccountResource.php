<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Account;

use OwnerPro\Asaas\Account\Request\AccountBankAccountRequest;
use OwnerPro\Asaas\Account\Request\CommercialInfoRequest;
use OwnerPro\Asaas\Account\Request\DeleteAccountRequest;
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

    /** @param string|resource $file */
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

    public function deleteDocumentFile(string $fileId): AsaasResult
    {
        return $this->connector->delete($this->documentFilePath($fileId));
    }

    public function bankAccount(): AsaasResult
    {
        return $this->connector->get(self::BASE.'/bankAccountInfo');
    }

    /** @param array<string, mixed>|AccountBankAccountRequest $data */
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

    private function documentFilesPath(string $documentId): string
    {
        return sprintf('%s/documents/%s/files', self::BASE, IdGuard::validate($documentId));
    }

    private function documentFilePath(string $fileId): string
    {
        return sprintf('%s/documents/files/%s', self::BASE, IdGuard::validate($fileId));
    }
}
