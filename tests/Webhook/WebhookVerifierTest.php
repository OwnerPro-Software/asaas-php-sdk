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

    expect($verifier->isFromAsaas('52.67.12.206'))->toBeTrue();
    expect($verifier->isFromAsaas('18.230.8.159'))->toBeTrue();
    expect($verifier->isFromAsaas('54.94.136.112'))->toBeTrue();
    expect($verifier->isFromAsaas('54.94.183.101'))->toBeTrue();
});

it('returns false when IP is not a known Asaas IP', function (): void {
    $verifier = new WebhookVerifier('token');

    expect($verifier->isFromAsaas('1.2.3.4'))->toBeFalse();
    expect($verifier->isFromAsaas('127.0.0.1'))->toBeFalse();
    expect($verifier->isFromAsaas(''))->toBeFalse();
});
