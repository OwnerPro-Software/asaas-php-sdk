<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\DocumentType;

it('exposes the KYC document types Asaas accepts', function (): void {
    expect(DocumentType::Identification->value)->toBe('IDENTIFICATION');
    expect(DocumentType::SocialContract->value)->toBe('SOCIAL_CONTRACT');
    expect(DocumentType::EntrepreneurRequirement->value)->toBe('ENTREPRENEUR_REQUIREMENT');
    expect(DocumentType::Minutes->value)->toBe('MINUTES');
    expect(DocumentType::Custom->value)->toBe('CUSTOM');
});

it('has exactly five cases', function (): void {
    expect(DocumentType::cases())->toHaveCount(5);
});
