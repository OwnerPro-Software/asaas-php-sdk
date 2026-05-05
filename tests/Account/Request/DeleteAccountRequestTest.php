<?php

declare(strict_types=1);

use OwnerPro\Asaas\Account\Request\DeleteAccountRequest;

mutates(DeleteAccountRequest::class);

it('serialises removeReason', function (): void {
    $req = new DeleteAccountRequest(removeReason: 'Migrating to another provider');

    expect($req->toArray())->toBe(['removeReason' => 'Migrating to another provider']);
});

it('builds from array', function (): void {
    $req = DeleteAccountRequest::fromArray(['removeReason' => 'Test']);

    expect($req->removeReason)->toBe('Test');
});

it('rejects empty removeReason', function (): void {
    new DeleteAccountRequest(removeReason: '');
})->throws(InvalidArgumentException::class, 'removeReason must not be empty');

it('throws on missing field via fromArray', function (): void {
    DeleteAccountRequest::fromArray([]);
})->throws(InvalidArgumentException::class, 'removeReason is required');
