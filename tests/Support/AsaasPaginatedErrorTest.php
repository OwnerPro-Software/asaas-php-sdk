<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\AsaasPaginatedError;
use OwnerPro\Asaas\Support\AsaasPaginationException;
use OwnerPro\Asaas\Support\AsaasRequestException;
use OwnerPro\Asaas\Support\RawResponse;

mutates([AsaasPaginatedError::class, AsaasPaginationException::class]);

it('exposes errors, response, offset and limit', function (): void {
    $rawResponse = RawResponse::fake(status: 400);

    $error = AsaasPaginatedError::fromApi(
        errors: [['code' => 'INVALID', 'description' => 'Bad request']],
        rawResponse: $rawResponse,
        offset: 20,
        limit: 10,
    );

    expect($error->errors)->toBe([['code' => 'INVALID', 'description' => 'Bad request']]);
    expect($error->response)->toBe($rawResponse);
    expect($error->offset)->toBe(20);
    expect($error->limit)->toBe(10);
});

it('exposes null response for connection errors', function (): void {
    $error = AsaasPaginatedError::fromApi(
        errors: [['code' => 'CONNECTION_ERROR', 'description' => 'Timeout']],
        rawResponse: null,
        offset: 0,
        limit: 100,
    );

    expect($error->response)->toBeNull();
});

it('orFail() throws AsaasRequestException with errors and response', function (): void {
    $rawResponse = RawResponse::fake(status: 422);

    $error = AsaasPaginatedError::fromApi(
        errors: [['code' => 'VALIDATION', 'description' => 'Field required']],
        rawResponse: $rawResponse,
        offset: 10,
        limit: 10,
    );

    $error->orFail();
})->throws(AsaasRequestException::class, 'Field required');

it('orFail() throws AsaasRequestException with null response', function (): void {
    $error = AsaasPaginatedError::fromApi(
        errors: [['code' => 'CONNECTION_ERROR', 'description' => 'Timeout']],
        rawResponse: null,
        offset: 0,
        limit: 100,
    );

    $error->orFail();
})->throws(AsaasRequestException::class, 'Timeout');

// A fault the walk diagnosed is not Asaas answering, so it must not surface as
// the type whose statusCode the caller reads as a verdict.
it('orFail() throws AsaasPaginationException for a fault the walk diagnosed', function (): void {
    $rawResponse = RawResponse::fake(status: 200);

    $error = AsaasPaginatedError::fromWalk(
        errors: [['code' => 'PAGINATION_SHORT', 'description' => 'Walk ended early']],
        rawResponse: $rawResponse,
        offset: 10,
        limit: 10,
    );

    try {
        $error->orFail();
    } catch (AsaasPaginationException $e) {
        expect($e->getMessage())->toBe('Walk ended early')
            ->and($e->errors)->toBe([['code' => 'PAGINATION_SHORT', 'description' => 'Walk ended early']])
            ->and($e->response)->toBe($rawResponse)
            ->and($e->offset)->toBe(10)
            ->and($e->limit)->toBe(10);

        return;
    }

    $this->fail('orFail() did not throw AsaasPaginationException');
});

it('orFail() falls back to a generic message when the fault carries no description', function (): void {
    AsaasPaginatedError::fromWalk(errors: [], rawResponse: null, offset: 0, limit: 100)->orFail();
})->throws(AsaasPaginationException::class, 'Asaas pagination fault');

it('serialises a pagination fault whole, so a log says which page stopped the walk', function (): void {
    $error = AsaasPaginatedError::fromWalk(
        errors: [['code' => 'PAGINATION_STALLED', 'description' => 'Same page twice']],
        rawResponse: null,
        offset: 30,
        limit: 15,
    );

    try {
        $error->orFail();
    } catch (AsaasPaginationException $e) {
        expect(json_decode((string) json_encode($e), true))->toBe([
            'message' => 'Same page twice',
            'errors' => [['code' => 'PAGINATION_STALLED', 'description' => 'Same page twice']],
            'response' => null,
            'offset' => 30,
            'limit' => 15,
        ]);
    }
});
