<?php

declare(strict_types=1);

use OwnerPro\Asaas\Invoice\Request\UpdateInvoiceRequest;

mutates(UpdateInvoiceRequest::class);

it('omits fields whose value is an explicit null', function (): void {
    // Laravel's $request->validated() legitimately yields null for an untouched
    // optional field. No request-body field is nullable, so null means "omit"
    // rather than a TypeError from the non-nullable constructor parameter.
    $request = UpdateInvoiceRequest::fromArray(['value' => null, 'observations' => 'keep']);

    expect($request->toArray())->toBe(['observations' => 'keep']);
});

it('carries every scalar field from fromArray() into the payload', function (): void {
    $payload = [
        'serviceDescription' => 'a service',
        'observations' => 'an observation',
        'value' => 100.0,
        'deductions' => 5.0,
        'effectiveDate' => '2026-01-01',
        'externalReference' => 'ref-1',
        'updatePayment' => true,
    ];

    expect(UpdateInvoiceRequest::fromArray($payload)->toArray())->toBe($payload);
});

it('coerces a taxes array into the Taxes DTO', function (): void {
    $taxes = ['retainIss' => false, 'iss' => 2.0, 'pis' => 0.0, 'cofins' => 0.0, 'csll' => 0.0, 'inss' => 0.0, 'ir' => 0.0];

    $request = UpdateInvoiceRequest::fromArray(['taxes' => $taxes]);

    expect($request->toArray())->toBe(['taxes' => $taxes]);
});
