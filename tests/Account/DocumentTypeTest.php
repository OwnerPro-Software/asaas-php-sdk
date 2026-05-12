<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\DocumentType;

mutates(DocumentType::class);

it('exposes every KYC document type accepted by Asaas', function (): void {
    expect(array_map(
        static fn (DocumentType $c): string => $c->value,
        DocumentType::cases(),
    ))->toBe([
        'ALLOW_BANK_ACCOUNT_DEPOSIT_STATEMENT',
        'CUSTOM',
        'EMANCIPATION_OF_MINORS',
        'ENTREPRENEUR_REQUIREMENT',
        'IDENTIFICATION_SELFIE',
        'IDENTIFICATION',
        'INVOICE',
        'MEI_CERTIFICATE',
        'MINUTES_OF_CONSTITUTION',
        'MINUTES_OF_ELECTION',
        'POWER_OF_ATTORNEY',
        'SOCIAL_CONTRACT',
    ]);
});

it('has exactly twelve cases', function (): void {
    expect(DocumentType::cases())->toHaveCount(12);
});
