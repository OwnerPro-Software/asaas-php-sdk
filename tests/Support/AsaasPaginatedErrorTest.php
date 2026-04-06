<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\RawResponse;

mutates(AsaasPaginatedError::class);

it('exposes errors, response, offset and limit', function (): void {
    $rawResponse = RawResponse::fake(status: 400);

    $error = new AsaasPaginatedError(
        errors: [['code' => 'INVALID', 'description' => 'Bad request']],
        response: $rawResponse,
        offset: 20,
        limit: 10,
    );

    expect($error->errors)->toBe([['code' => 'INVALID', 'description' => 'Bad request']]);
    expect($error->response)->toBe($rawResponse);
    expect($error->offset)->toBe(20);
    expect($error->limit)->toBe(10);
});

it('exposes null response for connection errors', function (): void {
    $error = new AsaasPaginatedError(
        errors: [['code' => 'CONNECTION_ERROR', 'description' => 'Timeout']],
        response: null,
        offset: 0,
        limit: 100,
    );

    expect($error->response)->toBeNull();
});

it('orFail() throws AsaasRequestException with errors and response', function (): void {
    $rawResponse = RawResponse::fake(status: 422);

    $error = new AsaasPaginatedError(
        errors: [['code' => 'VALIDATION', 'description' => 'Field required']],
        response: $rawResponse,
        offset: 10,
        limit: 10,
    );

    $error->orFail();
})->throws(AsaasRequestException::class, 'Field required');

it('orFail() throws AsaasRequestException with null response', function (): void {
    $error = new AsaasPaginatedError(
        errors: [['code' => 'CONNECTION_ERROR', 'description' => 'Timeout']],
        response: null,
        offset: 0,
        limit: 100,
    );

    $error->orFail();
})->throws(AsaasRequestException::class, 'Timeout');
