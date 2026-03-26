<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasRequestException;

mutates(AsaasRequestException::class);

it('stores errors and status code', function () {
    $errors = [['code' => 'invalid_value', 'description' => 'Value is required']];
    $exception = new AsaasRequestException($errors, 400);

    expect($exception->errors)->toBe($errors);
    expect($exception->statusCode)->toBe(400);
});

it('uses first error description as message', function () {
    $errors = [['description' => 'Customer not found']];
    $exception = new AsaasRequestException($errors, 404);

    expect($exception->getMessage())->toBe('Customer not found');
    expect($exception->getCode())->toBe(404);
});

it('falls back to default message when no description', function () {
    $exception = new AsaasRequestException([], 500);

    expect($exception->getMessage())->toBe('Asaas API error');
});

it('extends RuntimeException', function () {
    $exception = new AsaasRequestException([], 500);

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
