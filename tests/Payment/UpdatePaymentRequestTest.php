<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\BillingType;
use OwnerPro\Asaas\Payment\Request\UpdatePaymentRequest;
use OwnerPro\Asaas\Support\DTO\Callback;
use OwnerPro\Asaas\Support\DTO\Discount;
use OwnerPro\Asaas\Support\DTO\Fine;
use OwnerPro\Asaas\Support\DTO\Interest;
use OwnerPro\Asaas\Support\DTO\Split;
use OwnerPro\Asaas\Support\Missing;

mutates(UpdatePaymentRequest::class);

it('defaults every field to Missing when nothing is supplied', function (): void {
    $request = UpdatePaymentRequest::fromArray([]);

    expect($request->billingType)->toBe(Missing::Value);
    expect($request->value)->toBe(Missing::Value);
    expect($request->dueDate)->toBe(Missing::Value);
    expect($request->description)->toBe(Missing::Value);
    expect($request->externalReference)->toBe(Missing::Value);
    expect($request->discount)->toBe(Missing::Value);
    expect($request->interest)->toBe(Missing::Value);
    expect($request->fine)->toBe(Missing::Value);
    expect($request->postalService)->toBe(Missing::Value);
    expect($request->daysAfterDueDateToRegistrationCancellation)->toBe(Missing::Value);
    expect($request->split)->toBe(Missing::Value);
    expect($request->callback)->toBe(Missing::Value);
});

it('persists the new daysAfterDueDateToRegistrationCancellation and callback fields', function (): void {
    $request = UpdatePaymentRequest::fromArray([
        'value' => 200.00,
        'daysAfterDueDateToRegistrationCancellation' => 5,
        'callback' => ['successUrl' => 'https://example.com/ok', 'autoRedirect' => true],
    ]);

    expect($request->daysAfterDueDateToRegistrationCancellation)->toBe(5);
    expect($request->callback)->toBeInstanceOf(Callback::class);
    expect($request->callback->successUrl)->toBe('https://example.com/ok');
    expect($request->callback->autoRedirect)->toBeTrue();
});

it('omits Missing fields from toArray and includes set fields', function (): void {
    $request = new UpdatePaymentRequest(
        value: 200.00,
        daysAfterDueDateToRegistrationCancellation: 5,
        callback: new Callback(successUrl: 'https://example.com/ok'),
    );

    $array = $request->toArray();

    expect($array)->toHaveKeys(['value', 'daysAfterDueDateToRegistrationCancellation', 'callback']);
    expect($array)->not->toHaveKey('billingType');
    expect($array['daysAfterDueDateToRegistrationCancellation'])->toBe(5);
    expect($array['callback'])->toBe(['successUrl' => 'https://example.com/ok']);
});

it('coerces raw array callback to Callback DTO at construction time', function (): void {
    $request = new UpdatePaymentRequest(
        callback: ['successUrl' => 'https://example.com/ok', 'autoRedirect' => false],
    );

    expect($request->callback)->toBeInstanceOf(Callback::class);
    expect($request->callback->autoRedirect)->toBeFalse();
});

it('coerces discount/interest/fine from float, array, and DTO inputs', function (): void {
    $request = UpdatePaymentRequest::fromArray([
        'discount' => 5.0,
        'interest' => ['value' => 1.5],
        'fine' => ['value' => 2.0],
    ]);

    expect($request->discount)->toBeInstanceOf(Discount::class);
    expect($request->discount->value)->toBe(5.0);
    expect($request->interest)->toBeInstanceOf(Interest::class);
    expect($request->fine)->toBeInstanceOf(Fine::class);

    $direct = new UpdatePaymentRequest(
        discount: new Discount(value: 9.0),
        interest: 0.5,
        fine: 1.0,
    );

    expect($direct->discount)->toBeInstanceOf(Discount::class);
    expect($direct->interest)->toBeInstanceOf(Interest::class);
    expect($direct->interest->value)->toBe(0.5);
    expect($direct->fine)->toBeInstanceOf(Fine::class);
});

it('still accepts existing fields untouched (regression)', function (): void {
    $request = UpdatePaymentRequest::fromArray([
        'billingType' => BillingType::Pix,
        'value' => 200.00,
        'dueDate' => '2026-05-01',
        'split' => [['walletId' => 'wal_1', 'fixedValue' => 10.0]],
    ]);

    expect($request->billingType)->toBe(BillingType::Pix);
    expect($request->value)->toBe(200.00);
    expect($request->split)->toHaveCount(1);
    expect($request->split[0])->toBeInstanceOf(Split::class);
});
