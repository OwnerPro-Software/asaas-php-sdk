<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\Missing;
use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;

mutates(UpdateWebhookRequest::class);

it('creates from array with all fields', function (): void {
    $request = UpdateWebhookRequest::fromArray([
        'url' => 'https://example.com/hook',
        'name' => 'My Hook',
        'enabled' => true,
        'sendType' => 'SEQUENTIALLY',
        'authToken' => 'secret',
        'events' => ['PAYMENT_CONFIRMED'],
    ]);

    expect($request->url)->toBe('https://example.com/hook');
    expect($request->name)->toBe('My Hook');
    expect($request->enabled)->toBeTrue();
    expect($request->sendType)->toBe('SEQUENTIALLY');
    expect($request->authToken)->toBe('secret');
    expect($request->events)->toBe(['PAYMENT_CONFIRMED']);
});

it('creates from array with no fields defaults to Missing', function (): void {
    $request = UpdateWebhookRequest::fromArray([]);

    expect($request->url)->toBe(Missing::Value);
    expect($request->name)->toBe(Missing::Value);
    expect($request->enabled)->toBe(Missing::Value);
    expect($request->sendType)->toBe(Missing::Value);
    expect($request->authToken)->toBe(Missing::Value);
    expect($request->events)->toBe(Missing::Value);
});

it('masks authToken in debug info', function (): void {
    $request = new UpdateWebhookRequest(
        url: 'https://example.com/hook',
        name: 'My Hook',
        enabled: true,
        sendType: 'SEQUENTIALLY',
        authToken: 'super-secret-token',
        events: ['PAYMENT_CONFIRMED'],
    );

    $debug = $request->__debugInfo();

    expect($debug['url'])->toBe('https://example.com/hook');
    expect($debug['name'])->toBe('My Hook');
    expect($debug['enabled'])->toBeTrue();
    expect($debug['sendType'])->toBe('SEQUENTIALLY');
    expect($debug['authToken'])->toBe('***');
    expect($debug['events'])->toBe(['PAYMENT_CONFIRMED']);
});

it('omits authToken from debug info when Missing', function (): void {
    $request = new UpdateWebhookRequest(url: 'https://example.com/hook');

    expect($request->__debugInfo())->not->toHaveKey('authToken');
});

it('masks authToken in json serialization', function (): void {
    $request = new UpdateWebhookRequest(
        url: 'https://example.com/hook',
        authToken: 'super-secret-token',
    );

    $json = json_decode(json_encode($request), true);

    expect($json['url'])->toBe('https://example.com/hook');
    expect($json['authToken'])->toBe('***');
});

it('cannot be serialized', function (): void {
    $request = new UpdateWebhookRequest(
        url: 'https://example.com/hook',
        authToken: 'super-secret-token',
    );

    serialize($request);
})->throws(LogicException::class);

it('omits fields whose value is an explicit null', function (): void {
    $request = UpdateWebhookRequest::fromArray(['url' => null, 'enabled' => true]);

    expect($request->toArray())->toBe(['enabled' => true]);
});

it('carries every scalar field from fromArray() into the payload', function (): void {
    $payload = [
        'url' => 'https://example.test/hook',
        'name' => 'a name',
        'enabled' => true,
        'interrupted' => false,
        'sendType' => 'SEQUENTIALLY',
        'authToken' => 'secret',
    ];

    expect(UpdateWebhookRequest::fromArray($payload)->toArray())->toBe($payload);
});
