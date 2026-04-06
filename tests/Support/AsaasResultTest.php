<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\RawResponse;

mutates(AsaasResult::class);

it('creates a successful result with data', function (): void {
    $data = ['id' => 'abc'];
    $response = RawResponse::fake(200);
    $result = AsaasResult::success($data, $response);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe(['id' => 'abc']);
    expect($result->response)->toBe($response);
    expect($result->response->status())->toBe(200);
    expect($result->errors)->toBeNull();
});

it('creates a failed result with errors and response', function (): void {
    $errors = [['code' => 'invalid', 'description' => 'Bad request']];
    $response = RawResponse::fake(400);
    $result = AsaasResult::failure($errors, $response);

    expect($result->success)->toBeFalse();
    expect($result->data)->toBeNull();
    expect($result->errors)->toBe($errors);
    expect($result->response)->toBe($response);
    expect($result->response->status())->toBe(400);
});

it('creates a failed result with null response on connection error', function (): void {
    $errors = [['code' => 'CONNECTION_ERROR', 'description' => 'Timed out']];
    $result = AsaasResult::failure($errors);

    expect($result->success)->toBeFalse();
    expect($result->response)->toBeNull();
    expect($result->errors)->toBe($errors);
});

it('orFail() returns self on success', function (): void {
    $result = AsaasResult::success(['id' => 'abc'], RawResponse::fake(200));

    expect($result->orFail())->toBe($result);
});

it('orFail() throws AsaasRequestException on failure', function (): void {
    $errors = [['description' => 'Not found']];
    $response = RawResponse::fake(404);
    $result = AsaasResult::failure($errors, $response);

    try {
        $result->orFail();
    } catch (AsaasRequestException $e) {
        expect($e->getMessage())->toBe('Not found');
        expect($e->getCode())->toBe(404);
        expect($e->statusCode)->toBe(404);
        expect($e->errors)->toBe($errors);
        expect($e->response)->toBe($response);

        return;
    }

    test()->fail('Expected AsaasRequestException was not thrown');
});

it('orFail() throws with null response on connection error', function (): void {
    $errors = [['code' => 'CONNECTION_ERROR', 'description' => 'Timed out']];
    $result = AsaasResult::failure($errors);

    try {
        $result->orFail();
    } catch (AsaasRequestException $e) {
        expect($e->statusCode)->toBe(0);
        expect($e->response)->toBeNull();

        return;
    }

    test()->fail('Expected AsaasRequestException was not thrown');
});
