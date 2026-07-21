<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\PaymentDocumentType;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentDocumentRequest;

mutates(UpdatePaymentDocumentRequest::class);

it('builds from array and serialises the enum to its value', function (): void {
    $req = UpdatePaymentDocumentRequest::fromArray([
        'availableAfterPayment' => true,
        'type' => PaymentDocumentType::Invoice,
    ]);

    expect($req->toArray())->toBe([
        'availableAfterPayment' => true,
        'type' => 'INVOICE',
    ]);
});

it('accepts a string type for forward compatibility', function (): void {
    $req = UpdatePaymentDocumentRequest::fromArray([
        'availableAfterPayment' => false,
        'type' => 'CUSTOM',
    ]);

    expect($req->toArray())->toBe([
        'availableAfterPayment' => false,
        'type' => 'CUSTOM',
    ]);
});

it('rejects a missing required key with a descriptive exception', function (array $data, string $missing): void {
    expect(fn () => UpdatePaymentDocumentRequest::fromArray($data))
        ->toThrow(InvalidArgumentException::class, "UpdatePaymentDocumentRequest: {$missing} is required");
})->with([
    'availableAfterPayment' => [['type' => 'INVOICE'], 'availableAfterPayment'],
    'type' => [['availableAfterPayment' => true], 'type'],
]);

it('keeps a false availableAfterPayment out of the required-key guard', function (): void {
    $req = UpdatePaymentDocumentRequest::fromArray([
        'availableAfterPayment' => false,
        'type' => 'INVOICE',
    ]);

    expect($req->availableAfterPayment)->toBeFalse();
});
