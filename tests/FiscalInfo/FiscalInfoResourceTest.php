<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\FiscalInfo\FiscalInfoResource;
use OwnerPro\Asaas\FiscalInfo\Request\FiscalInfoRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\AsaasPaginatedResult;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\Environment;

mutates(FiscalInfoResource::class);

function fiscalInfoResource(): FiscalInfoResource
{
    return new FiscalInfoResource(AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30));
}

it('recovers fiscal info', function (): void {
    Http::fake(['*' => Http::response(['object' => 'customerFiscalInfo', 'email' => 'a@b.c'], 200)]);

    $result = fiscalInfoResource()->recover();

    expect($result)->toBeInstanceOf(AsaasResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data['email'])->toBe('a@b.c');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/fiscalInfo/');
});

it('saves fiscal info as multipart with no file', function (): void {
    Http::fake(['*' => Http::response(['object' => 'customerFiscalInfo'], 200)]);

    $result = fiscalInfoResource()->save([
        'email' => 'fiscal@asaas.com',
        'simplesNacional' => true,
        'cnae' => '6209100',
    ]);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($r): bool {
        if ($r->method() !== 'POST' || $r->url() !== 'https://api-sandbox.asaas.com/v3/fiscalInfo/') {
            return false;
        }
        if (! str_contains((string) $r->header('Content-Type')[0], 'multipart/form-data')) {
            return false;
        }
        $body = (string) $r->body();

        return str_contains($body, 'name="email"')
            && str_contains($body, 'fiscal@asaas.com')
            && str_contains($body, "name=\"simplesNacional\"\r\nContent-Length: 4\r\n\r\ntrue")
            && str_contains($body, 'name="cnae"');
    });
});

it('saves fiscal info as multipart with certificate file', function (): void {
    Http::fake(['*' => Http::response(['object' => 'customerFiscalInfo'], 200)]);

    fiscalInfoResource()->save(
        data: new FiscalInfoRequest(
            email: 'fiscal@asaas.com',
            simplesNacional: false,
            certificatePassword: 's3cret',
        ),
        certificateFile: 'binary-pfx-bytes',
        certificateFilename: 'my-cert.pfx',
    );

    Http::assertSent(function ($r): bool {
        $body = (string) $r->body();

        return str_contains($body, 'name="certificateFile"')
            && str_contains($body, 'filename="my-cert.pfx"')
            && str_contains($body, 'binary-pfx-bytes')
            && str_contains($body, "name=\"simplesNacional\"\r\nContent-Length: 5\r\n\r\nfalse")
            && str_contains($body, 's3cret');
    });
});

it('defaults the certificate filename when only the file content is provided', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    fiscalInfoResource()->save(
        data: ['email' => 'a@b.c', 'simplesNacional' => true],
        certificateFile: 'pfx-bytes',
    );

    Http::assertSent(fn ($r): bool => str_contains((string) $r->body(), 'filename="certificate.pfx"'));
});

it('fetches municipal options', function (): void {
    Http::fake(['*' => Http::response(['authenticationType' => 'CERTIFICATE'], 200)]);

    $result = fiscalInfoResource()->municipalOptions();

    expect($result->success)->toBeTrue();
    expect($result->data['authenticationType'])->toBe('CERTIFICATE');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && $r->url() === 'https://api-sandbox.asaas.com/v3/fiscalInfo/municipalOptions');
});

it('lists services with pagination', function (): void {
    Http::fake(['*' => Http::response(['data' => [['code' => '01']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0], 200)]);

    $result = fiscalInfoResource()->services(['limit' => 10]);

    expect($result)->toBeInstanceOf(AsaasPaginatedResult::class);
    expect($result->success)->toBeTrue();
    expect($result->data[0]['code'])->toBe('01');

    Http::assertSent(fn ($r): bool => $r->method() === 'GET'
        && str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/fiscalInfo/services')
        && str_contains($r->url(), 'limit=10'));
});

it('lists federal service codes', function (): void {
    Http::fake(['*' => Http::response(['data' => [['code' => '040801']], 'hasMore' => false, 'totalCount' => 1, 'limit' => 10, 'offset' => 0], 200)]);

    fiscalInfoResource()->federalServiceCodes(['code' => '040801']);

    Http::assertSent(fn ($r): bool => str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/fiscalInfo/federalServiceCodes')
        && str_contains($r->url(), 'code=040801'));
});

it('lists nbs codes', function (): void {
    Http::fake(['*' => Http::response(['data' => [], 'hasMore' => false, 'totalCount' => 0, 'limit' => 10, 'offset' => 0], 200)]);

    fiscalInfoResource()->nbsCodes();

    Http::assertSent(fn ($r): bool => str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/fiscalInfo/nbsCodes'));
});

it('lists operation indicator codes', function (): void {
    Http::fake(['*' => Http::response(['data' => [], 'hasMore' => false, 'totalCount' => 0, 'limit' => 10, 'offset' => 0], 200)]);

    fiscalInfoResource()->operationIndicatorCodes();

    Http::assertSent(fn ($r): bool => str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/fiscalInfo/operationIndicatorCodes'));
});

it('lists tax classification codes', function (): void {
    Http::fake(['*' => Http::response(['data' => [], 'hasMore' => false, 'totalCount' => 0, 'limit' => 10, 'offset' => 0], 200)]);

    fiscalInfoResource()->taxClassificationCodes();

    Http::assertSent(fn ($r): bool => str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/fiscalInfo/taxClassificationCodes'));
});

it('lists tax situation codes', function (): void {
    Http::fake(['*' => Http::response(['data' => [], 'hasMore' => false, 'totalCount' => 0, 'limit' => 10, 'offset' => 0], 200)]);

    fiscalInfoResource()->taxSituationCodes();

    Http::assertSent(fn ($r): bool => str_starts_with($r->url(), 'https://api-sandbox.asaas.com/v3/fiscalInfo/taxSituationCodes'));
});

it('configures national portal toggle', function (bool $enabled): void {
    Http::fake(['*' => Http::response(['success' => true], 200)]);

    $result = fiscalInfoResource()->configureNationalPortal(enabled: $enabled);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($r) use ($enabled): bool {
        if ($r->method() !== 'POST' || $r->url() !== 'https://api-sandbox.asaas.com/v3/fiscalInfo/nationalPortal') {
            return false;
        }
        $data = $r->data();

        return array_key_exists('enabled', $data) && $data['enabled'] === $enabled;
    });
})->with([true, false]);

it('propagates failure on recover', function (array $err): void {
    Http::fake(['*' => Http::response($err, 400)]);

    $result = fiscalInfoResource()->recover();

    expect($result->success)->toBeFalse();
    expect($result->errors[0]['code'])->toBe($err['errors'][0]['code']);
})->with('error_fixture');
