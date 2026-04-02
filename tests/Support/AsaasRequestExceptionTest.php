<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\RawResponse;

mutates(AsaasRequestException::class);

it('stores errors and response', function (): void {
    $response = RawResponse::fake(400);
    $errors = [['code' => 'invalid_value', 'description' => 'Value is required']];
    $exception = new AsaasRequestException($errors, $response);

    expect($exception->errors)->toBe($errors);
    expect($exception->response)->toBe($response);
    expect($exception->statusCode)->toBe(400);
});

it('uses first error description as message', function (): void {
    $errors = [['description' => 'Customer not found']];
    $exception = new AsaasRequestException($errors, RawResponse::fake(404));

    expect($exception->getMessage())->toBe('Customer not found');
    expect($exception->getCode())->toBe(404);
});

it('falls back to default message when no description', function (): void {
    $exception = new AsaasRequestException([], RawResponse::fake(500));

    expect($exception->getMessage())->toBe('Asaas API error');
});

it('derives status code zero when response is null', function (): void {
    $exception = new AsaasRequestException([['code' => 'CONNECTION_ERROR']], null);

    expect($exception->response)->toBeNull();
    expect($exception->statusCode)->toBe(0);
    expect($exception->getCode())->toBe(0);
});

it('extends RuntimeException', function (): void {
    $exception = new AsaasRequestException([], RawResponse::fake(500));

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
