<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\Request\PaymentCheckoutConfigRequest;

mutates(PaymentCheckoutConfigRequest::class);

it('builds from array with required fields only', function (): void {
    $req = PaymentCheckoutConfigRequest::fromArray([
        'logoBackgroundColor' => '#000',
        'infoBackgroundColor' => '#fff',
        'fontColor' => '#abc',
    ]);

    expect($req->toArray())->toBe([
        'logoBackgroundColor' => '#000',
        'infoBackgroundColor' => '#fff',
        'fontColor' => '#abc',
    ]);
});

it('includes enabled when supplied', function (): void {
    $req = PaymentCheckoutConfigRequest::fromArray([
        'logoBackgroundColor' => '#000',
        'infoBackgroundColor' => '#fff',
        'fontColor' => '#abc',
        'enabled' => false,
    ]);

    expect($req->toArray()['enabled'])->toBeFalse();
});

it('rejects a missing required key with a descriptive exception', function (array $data, string $missing): void {
    expect(fn () => PaymentCheckoutConfigRequest::fromArray($data))
        ->toThrow(InvalidArgumentException::class, "PaymentCheckoutConfigRequest: {$missing} is required");
})->with([
    'logoBackgroundColor' => [['infoBackgroundColor' => '#fff', 'fontColor' => '#abc'], 'logoBackgroundColor'],
    'infoBackgroundColor' => [['logoBackgroundColor' => '#000', 'fontColor' => '#abc'], 'infoBackgroundColor'],
    'fontColor' => [['logoBackgroundColor' => '#000', 'infoBackgroundColor' => '#fff'], 'fontColor'],
]);
