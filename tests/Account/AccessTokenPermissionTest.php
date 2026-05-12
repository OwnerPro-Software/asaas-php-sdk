<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\AccessTokenPermission;

mutates(AccessTokenPermission::class);

it('exposes all 33 documented permission codes', function (): void {
    expect(array_map(fn (AccessTokenPermission $c): string => $c->value, AccessTokenPermission::cases()))->toBe([
        'CUSTOMER',
        'CUSTOMER_NOTIFICATION',
        'PAYMENT',
        'PAYMENT_REFUND',
        'CHARGEBACK',
        'INSTALLMENT',
        'SUBSCRIPTION',
        'PIX_CREDIT',
        'PAYMENT_LINK',
        'CHECKOUT',
        'CREDIT_CARD',
        'ANTICIPATION',
        'ANTICIPATION_CONFIG',
        'ESCROW',
        'ESCROW_CONFIG',
        'CREDIT_BUREAU',
        'PAYMENT_DUNNING',
        'FISCAL_INFO',
        'INVOICE',
        'PIX_DEBIT',
        'PIX_ADDRESS_KEY',
        'PIX_RECURRING',
        'PIX_TRANSACTION',
        'PIX_AUTOMATIC',
        'TRANSFER',
        'BILL',
        'MOBILE_PHONE_RECHARGE',
        'FINANCIAL_TRANSACTION',
        'ACCOUNT_INFO',
        'PAYMENT_CHECKOUT_CONFIG',
        'WEBHOOK',
        'SUB_ACCOUNT',
        'ACCOUNT_DOCUMENT',
    ]);
});
