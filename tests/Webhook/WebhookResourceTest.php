<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\DeletedResponse;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;
use OwnerPro\Asaas\Webhook\Response\RemoveBackoffResponse;
use OwnerPro\Asaas\Webhook\Response\WebhookResponse;
use OwnerPro\Asaas\Webhook\WebhookResource;

mutates(WebhookResource::class);

function webhookConnector(): AsaasConnector
{
    return AsaasConnector::forLaravel('test-key', Environment::Sandbox, 30);
}

function webhookResource(): WebhookResource
{
    return new WebhookResource(webhookConnector());
}

dataset('webhook_fixture', [fn (): array => [
    'id' => 'wh_123', 'name' => 'My Hook', 'url' => 'https://example.com/hook',
    'email' => 'dev@test.com', 'enabled' => true, 'interrupted' => false,
    'apiVersion' => 3, 'hasAuthToken' => false, 'sendType' => 'SEQUENTIALLY',
    'penalizedRequestsCount' => 0, 'events' => ['PAYMENT_CONFIRMED'],
]]);

dataset('webhook_list_fixture', [fn (): array => [
    'object' => 'list', 'hasMore' => true, 'totalCount' => 3, 'limit' => 10, 'offset' => 0,
    'data' => [['id' => 'wh_1', 'url' => 'https://x.com', 'enabled' => true],
        ['id' => 'wh_2', 'url' => 'https://y.com', 'enabled' => true]],
]]);

it('creates a webhook', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->create(['url' => 'https://example.com/hook', 'email' => 'dev@test.com']);

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(WebhookResponse::class);
    expect($result->data->id)->toBe('wh_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks'
        && $request->method() === 'POST');
})->with('webhook_fixture');

it('creates a webhook from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->create(new CreateWebhookRequest(url: 'https://example.com/hook', email: 'dev@test.com'));

    expect($result->success)->toBeTrue();
    expect($result->data->id)->toBe('wh_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks'
        && $request->method() === 'POST');
})->with('webhook_fixture');

it('validates required fields', function (): void {
    webhookResource()->create(['url' => 'https://example.com']);
})->throws(InvalidArgumentException::class, "Field 'email' is required.");

it('lists webhooks', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->list();

    expect($result->success)->toBeTrue();
    expect($result->data)->toHaveCount(2);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api-sandbox.asaas.com/v3/webhooks'));
})->with('webhook_list_fixture');

it('finds a webhook', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->find('wh_123');

    expect($result->success)->toBeTrue();
    expect($result->data->url)->toBe('https://example.com/hook');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123');
})->with('webhook_fixture');

it('updates a webhook from array', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->update('wh_123', ['enabled' => false]);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123'
        && $request->method() === 'PUT');
})->with('webhook_fixture');

it('updates a webhook from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->update('wh_123', new UpdateWebhookRequest(enabled: false));

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123'
        && $request->method() === 'PUT');
})->with('webhook_fixture');

it('deletes a webhook', function (): void {
    Http::fake(['*' => Http::response(['deleted' => true, 'id' => 'wh_123'], 200)]);

    $result = webhookResource()->delete('wh_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(DeletedResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123'
        && $request->method() === 'DELETE');
});

it('removes backoff penalty', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    $result = webhookResource()->removeBackoff('wh_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeInstanceOf(RemoveBackoffResponse::class);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123/removeBackoff'
        && $request->method() === 'POST');
});

it('iterates all webhooks lazily', function (array $page1): void {
    $page2 = [
        'object' => 'list',
        'hasMore' => false,
        'totalCount' => 3,
        'limit' => 10,
        'offset' => 10,
        'data' => [['id' => 'wh_3', 'url' => 'https://z.com', 'enabled' => true]],
    ];

    Http::fakeSequence()
        ->push($page1, 200)
        ->push($page2, 200);

    $items = iterator_to_array(webhookResource()->all(['limit' => 10]));

    expect($items)->toHaveCount(3);
    expect($items[0])->toBeInstanceOf(WebhookResponse::class);
    expect($items[2]->id)->toBe('wh_3');
})->with('webhook_list_fixture');

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = webhookResource()->create(['url' => 'https://example.com', 'email' => 'dev@test.com']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
