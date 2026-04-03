<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Invoice\InvoiceResource;
use OwnerPro\Asaas\Invoice\Request\CreateInvoiceRequest;
use OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\DTO\Taxes;
use OwnerPro\Asaas\Support\Environment;

mutates(InvoiceResource::class);

function invoiceConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function invoiceResource(): InvoiceResource
{
    return new InvoiceResource(invoiceConnector());
}

dataset('invoice_fixture', [fn (): array => [
    'id' => 'inv_123', 'status' => 'SCHEDULED', 'customer' => 'cus_1',
    'serviceDescription' => 'Dev services', 'value' => 1000.00,
    'deductions' => 0, 'effectiveDate' => '2026-04-01',
    'observations' => 'Note', 'municipalServiceName' => 'IT Services',
    'taxes' => ['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 11.0, 'ir' => 1.5],
]]);

dataset('invoice_list_fixture', [fn (): array => [
    'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 10, 'offset' => 0,
    'data' => [['id' => 'inv_1', 'status' => 'SCHEDULED', 'value' => 500],
        ['id' => 'inv_2', 'status' => 'SCHEDULED', 'value' => 750]],
]]);

it('creates an invoice from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->create([
        'serviceDescription' => 'Dev services', 'observations' => 'Note',
        'value' => 1000.00, 'deductions' => 0, 'effectiveDate' => '2026-04-01',
        'municipalServiceName' => 'IT Services', 'taxes' => ['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 11.0, 'ir' => 1.5],
    ]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();
    expect($result->data['id'])->toBe('inv_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices'
        && $request->method() === 'POST');
})->with('invoice_fixture');

it('creates an invoice from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->create(new CreateInvoiceRequest(
        serviceDescription: 'Dev services',
        observations: 'Note',
        value: 1000.00,
        deductions: 0,
        effectiveDate: '2026-04-01',
        municipalServiceName: 'IT Services',
        taxes: ['retainIss' => true, 'iss' => 5.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 11.0, 'ir' => 1.5],
    ));

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('inv_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices'
        && $request->method() === 'POST');
})->with('invoice_fixture');

it('validates required fields', function (): void {
    invoiceResource()->create(['serviceDescription' => 'x']);
})->throws(TypeError::class);

it('lists invoices', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/invoices'));
})->with('invoice_list_fixture');

it('finds an invoice', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->find('inv_123');

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('inv_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices/inv_123');
})->with('invoice_fixture');

it('updates an invoice from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->update('inv_123', ['value' => 1500.00]);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices/inv_123'
        && $request->method() === 'PUT');
})->with('invoice_fixture');

it('updates an invoice from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->update('inv_123', new UpdateInvoiceRequest(value: 1500.00));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices/inv_123'
        && $request->method() === 'PUT');
})->with('invoice_fixture');

it('authorizes an invoice', function (array $fixture): void {
    $authorized = array_merge($fixture, ['status' => 'AUTHORIZED']);
    Http::fake(['*' => Http::response($authorized, 200)]);

    $result = invoiceResource()->authorize('inv_123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices/inv_123/authorize'
        && $request->method() === 'POST');
})->with('invoice_fixture');

it('cancels an invoice', function (array $fixture): void {
    $cancelled = array_merge($fixture, ['status' => 'CANCELLED']);
    Http::fake(['*' => Http::response($cancelled, 200)]);

    $result = invoiceResource()->cancel('inv_123');

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/invoices/inv_123/cancel'
        && $request->method() === 'POST');
})->with('invoice_fixture');

it('iterates all invoices lazily', function (array $page1): void {
    $page2 = [
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 3,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'inv_3', 'status' => 'AUTHORIZED', 'value' => 1200]],
    ];

    Http::fakeSequence()
        ->push($page1, 200)
        ->push($page2, 200);

    $items = iterator_to_array(invoiceResource()->all(['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeArray();
    expect($items[2]['id'])->toBe('inv_3');
})->with('invoice_list_fixture');

it('creates invoice with typed Taxes DTO', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->create(new CreateInvoiceRequest(
        serviceDescription: 'Service',
        observations: 'Obs',
        value: 100.00,
        deductions: 0.00,
        effectiveDate: '2026-03-26',
        municipalServiceName: 'IT Services',
        taxes: new Taxes(retainIss: true, iss: 2.0, pis: 0.65, cofins: 3.0, csll: 1.0, inss: 11.0, ir: 1.5),
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['taxes'] === ['retainIss' => true, 'iss' => 2.0, 'pis' => 0.65, 'cofins' => 3.0, 'csll' => 1.0, 'inss' => 11.0, 'ir' => 1.5];
    });
})->with('invoice_fixture');

it('updates invoice with typed Taxes DTO', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = invoiceResource()->update('inv_123', new UpdateInvoiceRequest(
        taxes: new Taxes(retainIss: false, iss: 3.0, pis: 1.0, cofins: 2.0, csll: 0.5, inss: 5.0, ir: 1.0),
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['taxes'] === ['retainIss' => false, 'iss' => 3.0, 'pis' => 1.0, 'cofins' => 2.0, 'csll' => 0.5, 'inss' => 5.0, 'ir' => 1.0];
    });
})->with('invoice_fixture');

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = invoiceResource()->create([
        'serviceDescription' => 'x', 'observations' => 'x',
        'value' => 0, 'deductions' => 0, 'effectiveDate' => '2026-04-01',
        'municipalServiceName' => 'x', 'taxes' => ['retainIss' => false, 'iss' => 0.0, 'pis' => 0.0, 'cofins' => 0.0, 'csll' => 0.0, 'inss' => 0.0, 'ir' => 0.0],
    ]);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
