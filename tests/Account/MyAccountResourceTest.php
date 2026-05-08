<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Account\DocumentType;
use OwnerPro\Asaas\Account\MyAccountResource;
use OwnerPro\Asaas\Account\Request\AccountBankAccountRequest;
use OwnerPro\Asaas\Account\Request\CommercialInfoRequest;
use OwnerPro\Asaas\Account\Request\DeleteAccountRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\BankAccountType;
use OwnerPro\Asaas\Support\Environment;

mutates(MyAccountResource::class);

function myAccountConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function myAccountResource(): MyAccountResource
{
    return new MyAccountResource(myAccountConnector());
}

it('fetches the account status from /myAccount/status', function (): void {
    Http::fake(['*' => Http::response([
        'general' => 'AWAITING_APPROVAL',
        'commercialInfo' => 'APPROVED',
        'documentation' => 'PENDING',
        'bankAccountInfo' => 'NOT_SENT',
    ], 200)]);

    $result = myAccountResource()->status();

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data)->toMatchArray([
        'general' => 'AWAITING_APPROVAL',
        'documentation' => 'PENDING',
    ]);

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/status');
});

it('propagates failure from /myAccount/status', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'Unauthorized']]], 401)]);

    $result = myAccountResource()->status();

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(401);
});

it('reads commercial info from /myAccount/commercialInfo', function (): void {
    Http::fake(['*' => Http::response([
        'name' => 'ACME', 'incomeValue' => 10000.0, 'companyType' => 'LIMITED',
    ], 200)]);

    $result = myAccountResource()->commercialInfo();

    expect($result->success)->toBeTrue();
    expect($result->data['companyType'])->toBe('LIMITED');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/commercialInfo');
});

it('updates commercial info from array payload', function (): void {
    Http::fake(['*' => Http::response(['updated' => true], 200)]);

    $result = myAccountResource()->updateCommercialInfo([
        'incomeValue' => 12000.0,
        'companyType' => 'LIMITED',
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/commercialInfo'
            && $request['incomeValue'] === 12000.0
            && $request['companyType'] === 'LIMITED';
    });
});

it('updates commercial info from CommercialInfoRequest object', function (): void {
    Http::fake(['*' => Http::response(['updated' => true], 200)]);

    $result = myAccountResource()->updateCommercialInfo(
        new CommercialInfoRequest(
            incomeValue: 7500.0,
        ),
    );

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request['incomeValue'] === 7500.0);
});

it('lists pending documents from /myAccount/documents', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 1,
        'limit' => 10,
        'offset' => 0,
        'data' => [[
            'id' => 'doc_1',
            'type' => 'IDENTIFICATION',
            'status' => 'PENDING',
            'title' => 'Documento de identificação',
        ]],
    ], 200)]);

    $result = myAccountResource()->documents();

    expect($result->success)->toBeTrue();
    expect($result->data['data'][0]['id'])->toBe('doc_1');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/documents');
});

it('uploads a KYC document file via multipart', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'file_1',
        'documentId' => 'doc_1',
        'status' => 'PROCESSING',
    ], 200)]);

    $path = __DIR__.'/../Fixtures/document_file.txt';
    expect(file_exists($path))->toBeTrue();

    $result = myAccountResource()->uploadDocumentFile(
        documentId: 'doc_1',
        file: fopen($path, 'rb'),
        type: DocumentType::Identification,
        filename: 'rg.png',
    );

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('file_1');

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/myAccount/documents/doc_1/files') {
            return false;
        }
        if (! str_contains((string) $request->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }

        return str_contains((string) $request->body(), 'IDENTIFICATION');
    });
});

it('rejects empty documentId on upload', function (): void {
    myAccountResource()->uploadDocumentFile(
        documentId: '',
        file: 'irrelevant',
        type: DocumentType::Identification,
        filename: 'x.png',
    );
})->throws(InvalidArgumentException::class);

it('accepts a string document type for forward compatibility', function (): void {
    Http::fake(['*' => Http::response(['id' => 'file_2'], 200)]);

    $result = myAccountResource()->uploadDocumentFile(
        documentId: 'doc_1',
        file: 'binary-bytes',
        type: 'CUSTOM_NEW_TYPE',
        filename: 'extra.png',
    );

    expect($result->success)->toBeTrue();
});

it('deletes a document file', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'file_1'], 200)]);

    $result = myAccountResource()->deleteDocumentFile('file_1');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/documents/files/file_1');
});

it('rejects empty fileId on deleteDocumentFile', function (): void {
    myAccountResource()->deleteDocumentFile('');
})->throws(InvalidArgumentException::class);

it('reads bank account info from /myAccount/bankAccountInfo', function (): void {
    Http::fake(['*' => Http::response([
        'bank' => ['code' => '341', 'name' => 'Itaú'],
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'bankAccountType' => 'CONTA_CORRENTE',
    ], 200)]);

    $result = myAccountResource()->bankAccount();

    expect($result->success)->toBeTrue();
    expect($result->data['bank']['code'])->toBe('341');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/bankAccountInfo');
});

it('updates bank account info from array', function (): void {
    Http::fake(['*' => Http::response(['updated' => true], 200)]);

    $result = myAccountResource()->updateBankAccount([
        'bankCode' => '341',
        'agency' => '1234',
        'account' => '56789',
        'accountDigit' => '0',
        'accountType' => 'CONTA_CORRENTE',
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/bankAccountInfo'
            && $request['bank']['code'] === '341'
            && $request['agency'] === '1234'
            && $request['bankAccountType'] === 'CONTA_CORRENTE';
    });
});

it('updates bank account from AccountBankAccountRequest', function (): void {
    Http::fake(['*' => Http::response(['updated' => true], 200)]);

    $result = myAccountResource()->updateBankAccount(
        new AccountBankAccountRequest(
            bankCode: '341',
            agency: '1',
            account: '1',
            accountDigit: '1',
            accountType: BankAccountType::CheckingAccount,
        ),
    );

    expect($result->success)->toBeTrue();
});

it('deletes the account with removeReason from array', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true], 200)]);

    $result = myAccountResource()->delete(['removeReason' => 'Migrating provider']);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        return $request->method() === 'DELETE'
            && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount?removeReason=Migrating%20provider';
    });
});

it('deletes the account with removeReason from DeleteAccountRequest', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true], 200)]);

    $result = myAccountResource()->delete(
        new DeleteAccountRequest(removeReason: 'Closing'),
    );

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount?removeReason=Closing');
});

it('rejects empty removeReason on delete', function (): void {
    myAccountResource()->delete(['removeReason' => '']);
})->throws(InvalidArgumentException::class);

it('rejects missing removeReason on delete', function (): void {
    myAccountResource()->delete([]);
})->throws(InvalidArgumentException::class);
