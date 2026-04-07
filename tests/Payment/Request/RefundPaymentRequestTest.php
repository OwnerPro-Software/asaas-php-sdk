<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\Request\RefundPaymentRequest;
use OwnerPro\Asaas\Support\DTO\SplitRefund;

mutates(RefundPaymentRequest::class);

it('hydrates splitRefunds from arrays into SplitRefund objects', function (): void {
    $request = RefundPaymentRequest::fromArray([
        'value' => 50.00,
        'splitRefunds' => [
            ['id' => 'split_1', 'value' => 25.00],
            ['id' => 'split_2', 'value' => 25.00],
        ],
    ]);

    expect($request->splitRefunds)->toHaveCount(2);
    expect($request->splitRefunds[0])->toBeInstanceOf(SplitRefund::class);
    expect($request->splitRefunds[0]->id)->toBe('split_1');
    expect($request->splitRefunds[1]->id)->toBe('split_2');
});
