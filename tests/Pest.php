<?php

declare(strict_types=1);

use OwnerPro\Asaas\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

dataset('error_fixture', [fn (): array => json_decode(file_get_contents(__DIR__.'/Fixtures/error_400.json'), true)]);
