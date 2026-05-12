<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\MultipartPayload;

mutates(MultipartPayload::class);

it('rewrites top-level booleans to literal strings', function (): void {
    expect(MultipartPayload::stringifyBooleans(['a' => true, 'b' => false, 'c' => 'kept']))->toBe([
        'a' => 'true',
        'b' => 'false',
        'c' => 'kept',
    ]);
});

it('recurses into nested arrays', function (): void {
    expect(MultipartPayload::stringifyBooleans(['outer' => ['inner' => true, 'list' => [false, 'x']]]))->toBe([
        'outer' => ['inner' => 'true', 'list' => ['false', 'x']],
    ]);
});

it('leaves non-boolean scalars and arrays untouched', function (): void {
    expect(MultipartPayload::stringifyBooleans([
        'int' => 42,
        'float' => 3.14,
        'null' => null,
        'string_true' => 'true',
        'empty_array' => [],
    ]))->toBe([
        'int' => 42,
        'float' => 3.14,
        'null' => null,
        'string_true' => 'true',
        'empty_array' => [],
    ]);
});
