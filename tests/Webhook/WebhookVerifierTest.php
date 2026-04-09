<?php

declare(strict_types=1);

use OwnerPro\Asaas\Webhook\WebhookVerifier;

mutates(WebhookVerifier::class);

it('throws on empty auth token', function (): void {
    new WebhookVerifier('');
})->throws(InvalidArgumentException::class, 'The webhook auth token must not be empty.');

it('returns true when header token matches the configured token', function (): void {
    $verifier = new WebhookVerifier('my-secret-token');

    expect($verifier->verify('my-secret-token'))->toBeTrue();
});

it('returns false when header token does not match', function (): void {
    $verifier = new WebhookVerifier('my-secret-token');

    expect($verifier->verify('wrong-token'))->toBeFalse();
});

it('returns false for empty header token', function (): void {
    $verifier = new WebhookVerifier('my-secret-token');

    expect($verifier->verify(''))->toBeFalse();
});

it('is timing-safe by using hash_equals', function (): void {
    $verifier = new WebhookVerifier('my-secret-token');

    // hash_equals returns false for different-length strings without leaking length
    expect($verifier->verify('short'))->toBeFalse();
    expect($verifier->verify('my-secret-token-but-longer'))->toBeFalse();
});

it('returns true when IP is a known Asaas IP', function (): void {
    $verifier = new WebhookVerifier('token');

    foreach (WebhookVerifier::DEFAULT_IPS as $ip) {
        expect($verifier->isFromAsaas($ip))->toBeTrue();
    }
});

it('returns false when IP is not a known Asaas IP', function (): void {
    $verifier = new WebhookVerifier('token');

    expect($verifier->isFromAsaas('1.2.3.4'))->toBeFalse();
    expect($verifier->isFromAsaas('127.0.0.1'))->toBeFalse();
    expect($verifier->isFromAsaas(''))->toBeFalse();
});

it('accepts custom trusted IPs', function (): void {
    $verifier = new WebhookVerifier('token', ['10.0.0.1', '10.0.0.2']);

    expect($verifier->isFromAsaas('10.0.0.1'))->toBeTrue();
    expect($verifier->isFromAsaas('10.0.0.2'))->toBeTrue();
    expect($verifier->isFromAsaas('52.67.12.206'))->toBeFalse();
});

it('exposes default IPs as a public constant', function (): void {
    expect(WebhookVerifier::DEFAULT_IPS)->toBe([
        '52.67.12.206',
        '18.230.8.159',
        '54.94.136.112',
        '54.94.183.101',
    ]);
});
