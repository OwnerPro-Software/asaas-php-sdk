<?php

declare(strict_types=1);

use OwnerPro\Asaas\Transfer\Request\InternalTransferRequest;

mutates(InternalTransferRequest::class);

it('builds from array with full shape', function (): void {
    $request = InternalTransferRequest::fromArray([
        'value' => 250.00,
        'walletId' => 'wal_abc123',
        'externalReference' => 'order-42',
    ]);

    expect($request->value)->toBe(250.00);
    expect($request->walletId)->toBe('wal_abc123');
    expect($request->externalReference)->toBe('order-42');
});

it('builds from array with only required fields', function (): void {
    $request = InternalTransferRequest::fromArray([
        'value' => 50.00,
        'walletId' => 'wal_xyz',
    ]);

    expect($request->value)->toBe(50.00);
    expect($request->walletId)->toBe('wal_xyz');
    expect($request->externalReference)->toBeNull();
});

it('throws when value is missing', function (): void {
    InternalTransferRequest::fromArray(['walletId' => 'wal_xyz']);
})->throws(InvalidArgumentException::class, 'InternalTransferRequest: value is required');

it('throws when walletId is missing', function (): void {
    InternalTransferRequest::fromArray(['value' => 10.0]);
})->throws(InvalidArgumentException::class, 'InternalTransferRequest: walletId is required');

it('serializes via toArray', function (): void {
    $request = new InternalTransferRequest(value: 100.0, walletId: 'wal_1', externalReference: 'ref');

    expect($request->toArray())->toBe([
        'value' => 100.0,
        'walletId' => 'wal_1',
        'externalReference' => 'ref',
    ]);
});
