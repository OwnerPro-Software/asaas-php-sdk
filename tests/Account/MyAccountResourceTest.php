<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Account\DocumentType;
use OwnerPro\Asaas\Account\MyAccountResource;
use OwnerPro\Asaas\Account\Request\AccountBankAccountRequest;
use OwnerPro\Asaas\Account\Request\CommercialInfoRequest;
use OwnerPro\Asaas\Account\Request\DeleteAccountRequest;
use OwnerPro\Asaas\Account\Request\PaymentCheckoutConfigRequest;
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

it('sends personType and companyName on the wire when present', function (): void {
    Http::fake(['*' => Http::response(['updated' => true], 200)]);

    myAccountResource()->updateCommercialInfo([
        'personType' => 'JURIDICA',
        'companyName' => 'Acme Ltda.',
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/commercialInfo'
        && $request['personType'] === 'JURIDICA'
        && $request['companyName'] === 'Acme Ltda.');
});

it('reads the account number from /myAccount/accountNumber', function (): void {
    Http::fake(['*' => Http::response(['agency' => '0001', 'account' => '12345', 'accountDigit' => '6'], 200)]);

    $result = myAccountResource()->accountNumber();

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/accountNumber');
});

it('reads fees from /myAccount/fees/', function (): void {
    Http::fake(['*' => Http::response(['boleto' => 1.99], 200)]);

    $result = myAccountResource()->fees();

    expect($result->success)->toBeTrue();
    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/fees/');
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

        $body = (string) $request->body();

        return str_contains($body, 'IDENTIFICATION') && str_contains($body, 'filename="rg.png"');
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

it('serialises every DocumentType case on the multipart body', function (DocumentType $type, string $expected): void {
    Http::fake(['*' => Http::response(['id' => 'file_x'], 200)]);

    myAccountResource()->uploadDocumentFile(
        documentId: 'doc_1',
        file: 'bytes',
        type: $type,
        filename: 'f.png',
    );

    Http::assertSent(fn ($request): bool => str_contains(
        (string) $request->body(),
        "name=\"type\"\r\nContent-Length: ".strlen($expected)."\r\n\r\n".$expected,
    ));
})->with([
    [DocumentType::AllowBankAccountDepositStatement, 'ALLOW_BANK_ACCOUNT_DEPOSIT_STATEMENT'],
    [DocumentType::Custom, 'CUSTOM'],
    [DocumentType::EmancipationOfMinors, 'EMANCIPATION_OF_MINORS'],
    [DocumentType::EntrepreneurRequirement, 'ENTREPRENEUR_REQUIREMENT'],
    [DocumentType::IdentificationSelfie, 'IDENTIFICATION_SELFIE'],
    [DocumentType::Identification, 'IDENTIFICATION'],
    [DocumentType::Invoice, 'INVOICE'],
    [DocumentType::MeiCertificate, 'MEI_CERTIFICATE'],
    [DocumentType::MinutesOfConstitution, 'MINUTES_OF_CONSTITUTION'],
    [DocumentType::MinutesOfElection, 'MINUTES_OF_ELECTION'],
    [DocumentType::PowerOfAttorney, 'POWER_OF_ATTORNEY'],
    [DocumentType::SocialContract, 'SOCIAL_CONTRACT'],
]);

it('finds a document file by id via GET /myAccount/documents/files/{id}', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'file_1',
        'documentId' => 'doc_1',
        'status' => 'APPROVED',
    ], 200)]);

    $result = myAccountResource()->findDocumentFile('file_1');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('file_1');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-sandbox.asaas.com/v3/myAccount/documents/files/file_1');
});

it('rejects empty fileId on findDocumentFile', function (): void {
    myAccountResource()->findDocumentFile('');
})->throws(InvalidArgumentException::class);

it('updates a document file via multipart POST /myAccount/documents/files/{id}', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'file_1',
        'documentId' => 'doc_1',
        'status' => 'PROCESSING',
    ], 200)]);

    $result = myAccountResource()->updateDocumentFile(
        fileId: 'file_1',
        file: 'replacement-bytes',
        type: DocumentType::IdentificationSelfie,
        filename: 'selfie.png',
    );

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        if ($request->url() !== 'https://api-sandbox.asaas.com/v3/myAccount/documents/files/file_1') {
            return false;
        }
        if (! str_contains((string) $request->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }

        $body = (string) $request->body();

        return str_contains($body, 'IDENTIFICATION_SELFIE')
            && str_contains($body, 'filename="selfie.png"')
            && str_contains($body, 'replacement-bytes');
    });
});

it('rejects empty fileId on updateDocumentFile', function (): void {
    myAccountResource()->updateDocumentFile(
        fileId: '',
        file: 'irrelevant',
        type: DocumentType::Identification,
        filename: 'x.png',
    );
})->throws(InvalidArgumentException::class);

it('accepts a raw string type on updateDocumentFile (no enum coercion)', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'file_str',
        'documentId' => 'doc_str',
        'status' => 'PROCESSING',
    ], 200)]);

    $result = myAccountResource()->updateDocumentFile(
        fileId: 'file_str',
        file: 'raw-bytes',
        type: 'FUTURE_DOCUMENT_TYPE',
        filename: 'future.png',
    );

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }
        $body = (string) $request->body();

        return str_contains($body, 'FUTURE_DOCUMENT_TYPE')
            && str_contains($body, 'filename="future.png"')
            && str_contains($body, 'raw-bytes');
    });
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

// --- paymentCheckoutConfig + wallets ---

it('fetches the payment checkout config', function (): void {
    Http::fake(['*' => Http::response(['status' => 'APPROVED', 'logoBackgroundColor' => '#fff'], 200)]);

    $result = myAccountResource()->paymentCheckoutConfig();

    expect($result->success)->toBeTrue();
    expect($result->data['status'])->toBe('APPROVED');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/myAccount/paymentCheckoutConfig/');
});

it('updates the payment checkout config without a logo file', function (): void {
    Http::fake(['*' => Http::response(['status' => 'AWAITING_APPROVAL'], 200)]);

    $result = myAccountResource()->updatePaymentCheckoutConfig([
        'logoBackgroundColor' => '#000000',
        'infoBackgroundColor' => '#ffffff',
        'fontColor' => '#222222',
        'enabled' => true,
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($r): bool {
        if ($r->method() !== 'POST' || $r->url() !== 'https://api-sandbox.asaas.com/v3/myAccount/paymentCheckoutConfig/') {
            return false;
        }
        if (! str_contains((string) $r->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }
        $body = (string) $r->body();

        return str_contains($body, '#000000')
            && str_contains($body, "name=\"enabled\"\r\nContent-Length: 4\r\n\r\ntrue");
    });
});

it('stringifies a false `enabled` on payment checkout config', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    myAccountResource()->updatePaymentCheckoutConfig(new PaymentCheckoutConfigRequest(
        logoBackgroundColor: '#000',
        infoBackgroundColor: '#fff',
        fontColor: '#222',
        enabled: false,
    ));

    Http::assertSent(fn ($r): bool => str_contains(
        (string) $r->body(),
        "name=\"enabled\"\r\nContent-Length: 5\r\n\r\nfalse",
    ));
});

it('updates the payment checkout config with a logo upload', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    myAccountResource()->updatePaymentCheckoutConfig(
        data: new PaymentCheckoutConfigRequest(
            logoBackgroundColor: '#abcdef',
            infoBackgroundColor: '#123456',
            fontColor: '#000000',
        ),
        logoFile: 'png-bytes',
        logoFilename: 'brand.png',
    );

    Http::assertSent(fn ($r): bool => str_contains((string) $r->body(), 'name="logoFile"')
        && str_contains((string) $r->body(), 'filename="brand.png"')
        && str_contains((string) $r->body(), 'png-bytes'));
});

it('defaults the logo filename when only the file content is provided', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    myAccountResource()->updatePaymentCheckoutConfig(
        data: [
            'logoBackgroundColor' => '#000', 'infoBackgroundColor' => '#fff', 'fontColor' => '#abc',
        ],
        logoFile: 'png',
    );

    Http::assertSent(fn ($r): bool => str_contains((string) $r->body(), 'filename="logo.png"'));
});

it('lists wallets via paginate', function (): void {
    Http::fake(['*' => Http::response(['data' => [['id' => 'wal_1']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0], 200)]);

    $result = myAccountResource()->wallets(['limit' => 5]);

    expect($result->success)->toBeTrue();
    expect($result->data[0]['id'])->toBe('wal_1');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/wallets/')
        && str_contains($r->url(), 'limit=5'));
});
