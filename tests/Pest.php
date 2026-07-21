<?php

declare(strict_types=1);

use OwnerPro\Asaas\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

dataset('error_fixture', [fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_400.json'), true)]);

dataset('error_envelope_fixture', [
    '403 forbidden' => [403, fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_403.json'), true)],
    '404 not found' => [404, fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_404.json'), true)],
    '422 validation errors' => [422, fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_422.json'), true)],
    '429 rate limit' => [429, fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_429.json'), true)],
    '400 bad request' => [400, fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_400.json'), true)],
]);
