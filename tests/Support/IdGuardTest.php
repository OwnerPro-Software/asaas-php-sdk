<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\IdGuard;

mutates(IdGuard::class);

it('accepts valid prefixed IDs', function (string $id): void {
    expect(IdGuard::validate($id))->toBe($id);
})->with([
    'cus_000005401844',
    'pay_080225913252',
    'pay_s02s330x4pox1x0y',
    'inv_000000000232',
    'sub_VXJBYgP2u0eO',
    'not_wuGp97JeCr7G',
]);

it('accepts valid UUID IDs', function (string $id): void {
    expect(IdGuard::validate($id))->toBe($id);
})->with([
    '2765d086-c7c5-5cca-898a-4262d212587c',
    '5a2c890b-dd63-4b5a-9169-96c8d7828f4c',
]);

it('accepts valid numeric IDs', function (string $id): void {
    expect(IdGuard::validate($id))->toBe($id);
})->with([
    '725104409743',
    '63997365512',
]);

it('accepts valid custom IDs', function (): void {
    expect(IdGuard::validate('ASAAS00000000000000383ASA'))->toBe('ASAAS00000000000000383ASA');
});

it('rejects IDs with path traversal', function (string $id): void {
    IdGuard::validate($id);
})->throws(InvalidArgumentException::class)->with([
    '../../v3/transfers',
    '../admin',
    'pay_123/../secret',
]);

it('rejects IDs with query string characters', function (string $id): void {
    IdGuard::validate($id);
})->throws(InvalidArgumentException::class)->with([
    'pay_123?customer=evil',
    'pay_123#fragment',
]);

it('rejects IDs with whitespace', function (string $id): void {
    IdGuard::validate($id);
})->throws(InvalidArgumentException::class)->with([
    'pay 123',
    "pay\n123",
    "pay\t123",
]);

it('rejects IDs with slashes', function (string $id): void {
    IdGuard::validate($id);
})->throws(InvalidArgumentException::class)->with([
    'pay/123',
    'pay\\123',
]);

it('rejects empty string', function (): void {
    IdGuard::validate('');
})->throws(InvalidArgumentException::class);
