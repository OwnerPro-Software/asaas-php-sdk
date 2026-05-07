<?php

declare(strict_types=1);

use OwnerPro\Asaas\PixAutomatic\PixAutomaticFrequency;
use OwnerPro\Asaas\PixAutomatic\Request\AuthorizationRequest;
use OwnerPro\Asaas\PixAutomatic\Request\ImmediateQrCode;

mutates(AuthorizationRequest::class);

it('creates from array with required fields only', function (): void {
    $request = AuthorizationRequest::fromArray([
        'frequency' => PixAutomaticFrequency::Monthly,
        'contractId' => 'CONTRACT-123',
        'startDate' => '2026-01-01',
        'customerId' => 'cus_001',
        'immediateQrCode' => ['expirationSeconds' => 3600, 'originalValue' => 100.0],
    ]);

    expect($request->frequency)->toBe(PixAutomaticFrequency::Monthly);
    expect($request->contractId)->toBe('CONTRACT-123');
    expect($request->startDate)->toBe('2026-01-01');
    expect($request->customerId)->toBe('cus_001');
    expect($request->immediateQrCode)->toBeInstanceOf(ImmediateQrCode::class);
    expect($request->immediateQrCode->expirationSeconds)->toBe(3600);
    expect($request->finishDate)->toBeNull();
    expect($request->value)->toBeNull();
    expect($request->description)->toBeNull();
    expect($request->minLimitValue)->toBeNull();
});

it('creates from array with all fields', function (): void {
    $request = AuthorizationRequest::fromArray([
        'frequency' => 'WEEKLY',
        'contractId' => 'CONTRACT-X',
        'startDate' => '2026-01-01',
        'customerId' => 'cus_001',
        'immediateQrCode' => ['expirationSeconds' => 3600, 'originalValue' => 100.0, 'description' => 'first'],
        'finishDate' => '2026-12-31',
        'value' => 50.0,
        'description' => 'streaming',
        'minLimitValue' => 30.0,
    ]);

    expect($request->frequency)->toBe('WEEKLY');
    expect($request->finishDate)->toBe('2026-12-31');
    expect($request->value)->toBe(50.0);
    expect($request->description)->toBe('streaming');
    expect($request->minLimitValue)->toBe(30.0);
});

it('coerces immediateQrCode array to DTO at construction', function (): void {
    $request = new AuthorizationRequest(
        frequency: PixAutomaticFrequency::Monthly,
        contractId: 'CONTRACT-1',
        startDate: '2026-01-01',
        customerId: 'cus_001',
        immediateQrCode: ['expirationSeconds' => 3600, 'originalValue' => 100.0],
    );

    expect($request->immediateQrCode)->toBeInstanceOf(ImmediateQrCode::class);
    expect($request->immediateQrCode->expirationSeconds)->toBe(3600);
});

it('accepts ImmediateQrCode instance directly', function (): void {
    $qr = new ImmediateQrCode(expirationSeconds: 7200, originalValue: 50.0);

    $request = new AuthorizationRequest(
        frequency: PixAutomaticFrequency::Monthly,
        contractId: 'CONTRACT-1',
        startDate: '2026-01-01',
        customerId: 'cus_001',
        immediateQrCode: $qr,
    );

    expect($request->immediateQrCode)->toBe($qr);
});

it('serializes nested DTO via toArray', function (): void {
    $request = new AuthorizationRequest(
        frequency: PixAutomaticFrequency::Monthly,
        contractId: 'CONTRACT-1',
        startDate: '2026-01-01',
        customerId: 'cus_001',
        immediateQrCode: new ImmediateQrCode(expirationSeconds: 3600, originalValue: 100.0),
    );

    $array = $request->toArray();

    expect($array['frequency'])->toBe('MONTHLY');
    expect($array['immediateQrCode'])->toBe(['expirationSeconds' => 3600, 'originalValue' => 100.0]);
});

it('throws when frequency is missing', function (): void {
    AuthorizationRequest::fromArray([
        'contractId' => 'X',
        'startDate' => '2026-01-01',
        'customerId' => 'c',
        'immediateQrCode' => ['expirationSeconds' => 1, 'originalValue' => 1.0],
    ]);
})->throws(InvalidArgumentException::class, 'AuthorizationRequest: frequency is required');

it('throws when contractId is missing', function (): void {
    AuthorizationRequest::fromArray([
        'frequency' => PixAutomaticFrequency::Monthly,
        'startDate' => '2026-01-01',
        'customerId' => 'c',
        'immediateQrCode' => ['expirationSeconds' => 1, 'originalValue' => 1.0],
    ]);
})->throws(InvalidArgumentException::class, 'AuthorizationRequest: contractId is required');

it('throws when startDate is missing', function (): void {
    AuthorizationRequest::fromArray([
        'frequency' => PixAutomaticFrequency::Monthly,
        'contractId' => 'X',
        'customerId' => 'c',
        'immediateQrCode' => ['expirationSeconds' => 1, 'originalValue' => 1.0],
    ]);
})->throws(InvalidArgumentException::class, 'AuthorizationRequest: startDate is required');

it('throws when customerId is missing', function (): void {
    AuthorizationRequest::fromArray([
        'frequency' => PixAutomaticFrequency::Monthly,
        'contractId' => 'X',
        'startDate' => '2026-01-01',
        'immediateQrCode' => ['expirationSeconds' => 1, 'originalValue' => 1.0],
    ]);
})->throws(InvalidArgumentException::class, 'AuthorizationRequest: customerId is required');

it('throws when immediateQrCode is missing', function (): void {
    AuthorizationRequest::fromArray([
        'frequency' => PixAutomaticFrequency::Monthly,
        'contractId' => 'X',
        'startDate' => '2026-01-01',
        'customerId' => 'c',
    ]);
})->throws(InvalidArgumentException::class, 'AuthorizationRequest: immediateQrCode is required');
