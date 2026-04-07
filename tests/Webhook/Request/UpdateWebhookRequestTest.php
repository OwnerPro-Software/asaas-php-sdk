<?php

declare(strict_types=1);

use OwnerPro\Asaas\Webhook\Request\UpdateWebhookRequest;

mutates(UpdateWebhookRequest::class);

it('creates from array with all fields', function (): void {
    $request = UpdateWebhookRequest::fromArray([
        'url' => 'https://example.com/hook',
        'email' => 'dev@test.com',
        'name' => 'My Hook',
        'enabled' => true,
        'apiVersion' => 3,
        'sendType' => 'SEQUENTIALLY',
        'authToken' => 'secret',
        'events' => ['PAYMENT_CONFIRMED'],
    ]);

    expect($request->url)->toBe('https://example.com/hook');
    expect($request->email)->toBe('dev@test.com');
    expect($request->name)->toBe('My Hook');
    expect($request->enabled)->toBeTrue();
    expect($request->apiVersion)->toBe(3);
    expect($request->sendType)->toBe('SEQUENTIALLY');
    expect($request->authToken)->toBe('secret');
    expect($request->events)->toBe(['PAYMENT_CONFIRMED']);
});

it('creates from array with no fields', function (): void {
    $request = UpdateWebhookRequest::fromArray([]);

    expect($request->url)->toBeNull();
    expect($request->email)->toBeNull();
    expect($request->name)->toBeNull();
    expect($request->enabled)->toBeNull();
    expect($request->apiVersion)->toBeNull();
    expect($request->sendType)->toBeNull();
    expect($request->authToken)->toBeNull();
    expect($request->events)->toBeNull();
});

it('masks authToken in debug info', function (): void {
    $request = new UpdateWebhookRequest(
        url: 'https://example.com/hook',
        email: 'dev@test.com',
        name: 'My Hook',
        enabled: true,
        apiVersion: 3,
        sendType: 'SEQUENTIALLY',
        authToken: 'super-secret-token',
        events: ['PAYMENT_CONFIRMED'],
    );

    $debug = $request->__debugInfo();

    expect($debug['url'])->toBe('https://example.com/hook');
    expect($debug['email'])->toBe('dev@test.com');
    expect($debug['name'])->toBe('My Hook');
    expect($debug['enabled'])->toBeTrue();
    expect($debug['apiVersion'])->toBe(3);
    expect($debug['sendType'])->toBe('SEQUENTIALLY');
    expect($debug['authToken'])->toBe('***');
    expect($debug['events'])->toBe(['PAYMENT_CONFIRMED']);
});

it('shows null authToken as null in debug info', function (): void {
    $request = new UpdateWebhookRequest(url: 'https://example.com/hook');

    expect($request->__debugInfo()['authToken'])->toBeNull();
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
