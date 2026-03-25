<?php

declare(strict_types=1);

use OwnerPro\Asaas\AsaasClient;

it('can be instantiated with valid config', function (): void {
    $client = new AsaasClient(
        apiKey: 'test_key',
        environment: 'sandbox',
        timeout: 30,
    );

    expect($client->apiKey())->toBe('test_key');
    expect($client->environment())->toBe('sandbox');
    expect($client->timeout())->toBe(30);
});
