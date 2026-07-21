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

// `POST /accounts` answers with the subaccount's live API key, so the result
// object puts a credential on a public property. Every debug path that prints
// a result is a disclosure path unless the body is scrubbed.
it('keeps a credential returned by Asaas out of debug output', function (): void {
    $data = ['id' => 'acc_1', 'walletId' => 'w_1', 'apiKey' => '$aact_LIVE_SUBACCOUNT_KEY'];
    $result = AsaasResult::success($data, RawResponse::fake(200));

    expect($result->__debugInfo()['data'])
        ->toBe(['id' => 'acc_1', 'walletId' => 'w_1', 'apiKey' => '***']);
});

it('keeps a credential returned by Asaas out of print_r and var_dump', function (): void {
    $result = AsaasResult::success(['apiKey' => '$aact_LIVE_SUBACCOUNT_KEY'], RawResponse::fake(200));

    ob_start();
    var_dump($result);
    $dumped = (string) ob_get_clean();

    expect(print_r($result, true))->not->toContain('$aact_LIVE_SUBACCOUNT_KEY')
        ->and($dumped)->not->toContain('$aact_LIVE_SUBACCOUNT_KEY');
});

it('still exposes the real credential on the data property, so callers can store it', function (): void {
    $result = AsaasResult::success(['apiKey' => '$aact_LIVE_SUBACCOUNT_KEY'], RawResponse::fake(200));

    expect($result->data['apiKey'])->toBe('$aact_LIVE_SUBACCOUNT_KEY');
});

it('reports every result field verbatim in debug output when no secret is present', function (): void {
    $response = RawResponse::fake(200);
    $result = AsaasResult::success(['id' => 'pay_1', 'value' => 100.0], $response);

    expect($result->__debugInfo())->toBe([
        'success' => true,
        'data' => ['id' => 'pay_1', 'value' => 100.0],
        'errors' => null,
        'response' => $response,
    ]);
});

it('carries a null data through debug output rather than scrubbing it', function (): void {
    $result = AsaasResult::failure([['code' => 'invalid', 'description' => 'bad']]);

    expect($result->__debugInfo())->toBe([
        'success' => false,
        'data' => null,
        'errors' => [['code' => 'invalid', 'description' => 'bad']],
        'response' => null,
    ]);
});

it('scrubs a credential carried as a field on a canonical error row', function (): void {
    // ErrorEnvelope scrubs the body it pastes into a synthesized description;
    // a canonical row is passed through untouched, so the field-name scrub has
    // to happen here as well. Neither layer covers the other.
    $result = AsaasResult::failure(
        [['code' => 'x', 'description' => 'see key', 'apiKey' => 'aact_prod_LIVEKEY123']],
        RawResponse::fake(status: 400),
    );

    expect(json_encode($result))->not->toContain('aact_prod_LIVEKEY123');
    expect($result->__debugInfo()['errors'])
        ->toBe([['code' => 'x', 'description' => 'see key', 'apiKey' => '***']]);
});

it('leaves a null errors list null rather than scrubbing it into an array', function (): void {
    expect(AsaasResult::success(['id' => 'pay_1'], RawResponse::fake())->__debugInfo()['errors'])->toBeNull();
});
