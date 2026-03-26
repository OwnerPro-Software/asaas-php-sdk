<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\AsaasResult;
use OwnerPro\Asaas\Support\BaseDTO;

mutates(AsaasResult::class);

// Concrete test DTO
final class ResultTestDTO extends BaseDTO
{
    public string $id;
}

it('creates a successful result with data', function () {
    $dto = new ResultTestDTO(['id' => 'abc']);
    $result = AsaasResult::success($dto, 200);

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('abc');
    expect($result->statusCode)->toBe(200);
    expect($result->errors)->toBeNull();
});

it('creates a failed result with errors', function () {
    $errors = [['code' => 'invalid', 'description' => 'Bad request']];
    $result = AsaasResult::failure($errors, 400);

    expect($result->success)->toBeFalse();
    expect($result->data)->toBeNull();
    expect($result->errors)->toBe($errors);
    expect($result->statusCode)->toBe(400);
});

it('throw() returns self on success', function () {
    $dto = new ResultTestDTO(['id' => 'abc']);
    $result = AsaasResult::success($dto, 200);

    expect($result->throw())->toBe($result);
});

it('throw() throws AsaasRequestException on failure', function () {
    $errors = [['description' => 'Not found']];
    $result = AsaasResult::failure($errors, 404);

    try {
        $result->throw();
    } catch (AsaasRequestException $e) {
        expect($e->getMessage())->toBe('Not found');
        expect($e->getCode())->toBe(404);
        expect($e->statusCode)->toBe(404);
        expect($e->errors)->toBe($errors);

        return;
    }

    test()->fail('Expected AsaasRequestException was not thrown');
});
