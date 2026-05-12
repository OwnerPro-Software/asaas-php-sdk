<?php

declare(strict_types=1);

use OwnerPro\Asaas\Payment\PaymentDocumentType;

mutates(PaymentDocumentType::class);

it('exposes all 7 documented document types', function (): void {
    expect(array_map(fn (PaymentDocumentType $c): string => $c->value, PaymentDocumentType::cases()))->toBe([
        'INVOICE', 'CONTRACT', 'MEDIA', 'DOCUMENT', 'SPREADSHEET', 'PROGRAM', 'OTHER',
    ]);
});
