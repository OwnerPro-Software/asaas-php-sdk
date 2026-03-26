<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\CreditCard\CreditCardResource;
use OwnerPro\Asaas\CreditCard\Request\SetPreAuthConfigRequest;
use OwnerPro\Asaas\CreditCard\Request\TokenizeCreditCardRequest;
use OwnerPro\Asaas\CreditCard\Response\CreditCardResponse;
use OwnerPro\Asaas\CreditCard\Response\PreAuthConfigResponse;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\DTO\CreditCard;
use OwnerPro\Asaas\Support\DTO\CreditCardHolderInfo;

mutates(CreditCardResource::class);

function ccConnector(): AsaasConnector
{
    return new AsaasConnector('test-key', 'sandbox', 30);
}

function creditCardResource(): CreditCardResource
{
    return new CreditCardResource(ccConnector());
}

it('tokenizes a credit card from array', function (): void {
    Http::fake(['*' => Http::response([
        'creditCardNumber' => '8829', 'creditCardBrand' => 'VISA', 'creditCardToken' => 'tok_abc',
    ], 200)]);

    $result = creditCardResource()->tokenize([
        'customer' => 'cus_1',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '1199999'],
        'remoteIp' => '127.0.0.1',
    ]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(CreditCardResponse::class);
    expect($result->data->creditCardToken)->toBe('tok_abc');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/creditCard/tokenizeCreditCard'
        && $request->method() === 'POST');
});

it('tokenizes a credit card from request object', function (): void {
    Http::fake(['*' => Http::response([
        'creditCardNumber' => '8829', 'creditCardBrand' => 'VISA', 'creditCardToken' => 'tok_abc',
    ], 200)]);

    $result = creditCardResource()->tokenize(new TokenizeCreditCardRequest(
        customer: 'cus_1',
        creditCard: ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        creditCardHolderInfo: ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '1199999'],
        remoteIp: '127.0.0.1',
    ));

    expect($result->success)->toBeTrue();
    expect($result->data->creditCardToken)->toBe('tok_abc');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/creditCard/tokenizeCreditCard'
        && $request->method() === 'POST');
});

it('validates required fields for tokenize', function (): void {
    creditCardResource()->tokenize(['customer' => 'cus_1']);
})->throws(InvalidArgumentException::class, "Field 'creditCard' is required.");

it('gets pre-authorization config', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 5], 200)]);

    $result = creditCardResource()->getPreAuthorizationConfig();

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PreAuthConfigResponse::class);
    expect($result->data->daysToExpire)->toBe(5);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/creditCard/preAuthorization/config');
});

it('sets pre-authorization config from array', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 7], 200)]);

    $result = creditCardResource()->setPreAuthorizationConfig(['daysToExpire' => 7]);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(PreAuthConfigResponse::class);
    expect($result->data->daysToExpire)->toBe(7);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/creditCard/preAuthorization/config'
        && $request->method() === 'POST');
});

it('sets pre-authorization config from request object', function (): void {
    Http::fake(['*' => Http::response(['daysToExpire' => 7], 200)]);

    $result = creditCardResource()->setPreAuthorizationConfig(new SetPreAuthConfigRequest(daysToExpire: 7));

    expect($result->success)->toBeTrue();
    expect($result->data->daysToExpire)->toBe(7);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/creditCard/preAuthorization/config'
        && $request->method() === 'POST');
});

it('validates required fields for setPreAuthorizationConfig', function (): void {
    creditCardResource()->setPreAuthorizationConfig([]);
})->throws(InvalidArgumentException::class, "Field 'daysToExpire' is required.");

it('tokenizes with typed CreditCard DTOs', function (): void {
    Http::fake(['*' => Http::response(['creditCardNumber' => '1111', 'creditCardBrand' => 'VISA', 'creditCardToken' => 'tok_123'], 200)]);

    $result = creditCardResource()->tokenize(new TokenizeCreditCardRequest(
        customer: 'cus_456',
        creditCard: new CreditCard(holderName: 'John', number: '4111111111111111', expiryMonth: '12', expiryYear: '2030', ccv: '123'),
        creditCardHolderInfo: new CreditCardHolderInfo(name: 'John', email: 'j@t.com', cpfCnpj: '123', postalCode: '01001000', addressNumber: '1', phone: '11999'),
        remoteIp: '127.0.0.1',
    ));

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['creditCard'] === ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'];
    });
});

it('returns failure on API error', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['description' => 'Invalid card data']]], 400)]);

    $result = creditCardResource()->tokenize([
        'customer' => 'cus_1',
        'creditCard' => ['holderName' => 'John', 'number' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2030', 'ccv' => '123'],
        'creditCardHolderInfo' => ['name' => 'John', 'email' => 'j@t.com', 'cpfCnpj' => '123', 'postalCode' => '01001000', 'addressNumber' => '1', 'phone' => '1199999'],
        'remoteIp' => '127.0.0.1',
    ]);

    expect($result->success)->toBeFalse();
    expect($result->statusCode)->toBe(400);
});
