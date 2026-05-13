<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OwnerPro\Asaas\Support\AsaasConnector;
use OwnerPro\Asaas\Support\Environment;
use OwnerPro\Asaas\Webhook\Request\CreateWebhookRequest;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;
use OwnerPro\Asaas\Webhook\WebhookEvent;
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
    expect($result->data)->toBeArray();
    expect($result->data['id'])->toBe('wh_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks'
        && $request->method() === 'POST');
})->with('webhook_fixture');

it('creates a webhook from request object', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    $result = webhookResource()->create(new CreateWebhookRequest(url: 'https://example.com/hook', email: 'dev@test.com'));

    expect($result->success)->toBeTrue();
    expect($result->data['id'])->toBe('wh_123');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks'
        && $request->method() === 'POST');
})->with('webhook_fixture');

it('validates required fields', function (): void {
    webhookResource()->create(['url' => 'https://example.com']);
})->throws(InvalidArgumentException::class);

it('sends interrupted=false by default on create to satisfy Asaas validator', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->create(new CreateWebhookRequest(url: 'https://example.com/hook', email: 'dev@test.com'));

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && ($request->data()['interrupted'] ?? null) === false);
})->with('webhook_fixture');

it('sends interrupted=true when explicitly set on create', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->create(new CreateWebhookRequest(
        url: 'https://example.com/hook',
        email: 'dev@test.com',
        interrupted: true,
    ));

    Http::assertSent(fn ($request): bool => ($request->data()['interrupted'] ?? null) === true);
})->with('webhook_fixture');

it('accepts interrupted from raw array on create', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->create([
        'url' => 'https://example.com/hook',
        'email' => 'dev@test.com',
        'interrupted' => true,
    ]);

    Http::assertSent(fn ($request): bool => ($request->data()['interrupted'] ?? null) === true);
})->with('webhook_fixture');

it('serialises WebhookEvent enum and raw strings interchangeably on create body', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->create(new CreateWebhookRequest(
        url: 'https://example.com/hook',
        email: 'dev@test.com',
        events: [
            WebhookEvent::PaymentConfirmed,
            'PAYMENT_RECEIVED',
            WebhookEvent::TransferDone,
        ],
    ));

    Http::assertSent(fn ($request): bool => ($request->data()['events'] ?? null) === [
        'PAYMENT_CONFIRMED',
        'PAYMENT_RECEIVED',
        'TRANSFER_DONE',
    ]);
})->with('webhook_fixture');

it('serialises WebhookEvent enum on update body', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->update('wh_123', new UpdateWebhookRequest(
        events: [WebhookEvent::PixAutomaticRecurringEligibilityUpdated, 'PAYMENT_OVERDUE'],
    ));

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && ($request->data()['events'] ?? null) === [
            'PIX_AUTOMATIC_RECURRING_ELIGIBILITY_UPDATED',
            'PAYMENT_OVERDUE',
        ]);
})->with('webhook_fixture');

it('sends interrupted on update when set', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->update('wh_123', new UpdateWebhookRequest(interrupted: false));

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && ($request->data()['interrupted'] ?? null) === false);
})->with('webhook_fixture');

it('omits interrupted from update payload when not set', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->update('wh_123', new UpdateWebhookRequest(enabled: false));

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && ! array_key_exists('interrupted', $request->data()));
})->with('webhook_fixture');

it('UpdateWebhookRequest: omitted fields never reach the wire as null', function (array $fixture): void {
    Http::fake(['*' => Http::response($fixture, 200)]);

    webhookResource()->update('wh_123', new UpdateWebhookRequest(enabled: false));

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body === ['enabled' => false]
            && ! array_key_exists('url', $body)
            && ! array_key_exists('name', $body)
            && ! array_key_exists('email', $body)
            && ! array_key_exists('authToken', $body);
    });
})->with('webhook_fixture');

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
    expect($result->data['url'])->toBe('https://example.com/hook');

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
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123'
        && $request->method() === 'DELETE');
});

it('removes backoff penalty', function (): void {
    Http::fake(['*' => Http::response([], 200)]);

    $result = webhookResource()->removeBackoff('wh_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBeArray();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/webhooks/wh_123/removeBackoff'
        && $request->method() === 'POST');
});

it('treats 204 No Content as success on removeBackoff', function (): void {
    Http::fake(['*' => Http::response('', 204)]);

    $result = webhookResource()->removeBackoff('wh_123');

    expect($result->success)->toBeTrue();
    expect($result->data)->toBe([]);
    expect($result->response->status())->toBe(204);
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
    expect($items[0])->toBeArray();
    expect($items[2]['id'])->toBe('wh_3');
})->with('webhook_list_fixture');

it('returns failure on API error', function (array $errorFixture): void {
    Http::fake(['*' => Http::response($errorFixture, 400)]);

    $result = webhookResource()->create(['url' => 'https://example.com', 'email' => 'dev@test.com']);

    expect($result->success)->toBeFalse();
    expect($result->response->status())->toBe(400);
    expect($result->errors[0]['description'])->toBe('The value field is required');
})->with('error_fixture');
