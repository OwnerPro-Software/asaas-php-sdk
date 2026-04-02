<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\BaseResponse;
use OwnerPro\Asaas\Support\RawResponse;

mutates(AsaasResult::class);

// Concrete test response
final class ResultTestResponse extends BaseResponse
{
    public string $id;
}

it('creates a successful result with data', function (): void {
    $dto = new ResultTestResponse(['id' => 'abc']);
    $response = RawResponse::fake(200);
    $result = AsaasResult::success($dto, $response);

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('abc');
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

it('throw() returns self on success', function (): void {
    $dto = new ResultTestResponse(['id' => 'abc']);
    $result = AsaasResult::success($dto, RawResponse::fake(200));

    expect($result->throw())->toBe($result);
});

it('throw() throws AsaasRequestException on failure', function (): void {
    $errors = [['description' => 'Not found']];
    $response = RawResponse::fake(404);
    $result = AsaasResult::failure($errors, $response);

    try {
        $result->throw();
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

it('throw() throws with null response on connection error', function (): void {
    $errors = [['code' => 'CONNECTION_ERROR', 'description' => 'Timed out']];
    $result = AsaasResult::failure($errors);

    try {
        $result->throw();
    } catch (AsaasRequestException $e) {
        expect($e->statusCode)->toBe(0);
        expect($e->response)->toBeNull();

        return;
    }

    test()->fail('Expected AsaasRequestException was not thrown');
});
