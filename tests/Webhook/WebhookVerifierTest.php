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

it('accepts the IPv4-mapped IPv6 form a dual-stack listener reports', function (): void {
    $verifier = new WebhookVerifier('token');

    expect($verifier->isFromAsaas('::ffff:52.67.12.206'))->toBeTrue();
});

it('matches an IPv4 caller against an allowlist written in IPv4-mapped form', function (): void {
    $verifier = new WebhookVerifier('token', ['::ffff:10.0.0.1']);

    expect($verifier->isFromAsaas('10.0.0.1'))->toBeTrue();
});

it('still rejects a mapped address outside the allowlist', function (): void {
    $verifier = new WebhookVerifier('token');

    expect($verifier->isFromAsaas('::ffff:1.2.3.4'))->toBeFalse();
});

it('matches genuine IPv6 allowlist entries in any notation', function (): void {
    $verifier = new WebhookVerifier('token', ['2001:0db8:0000:0000:0000:0000:0000:0001']);

    expect($verifier->isFromAsaas('2001:db8::1'))->toBeTrue();
});

it('does not confuse distinct IPv6 addresses that share their last four bytes', function (): void {
    // Only the IPv4-mapped range may be folded to 4 bytes: folding every 16-byte
    // address would make any two IPv6 hosts with the same suffix compare equal.
    $verifier = new WebhookVerifier('token', ['2001:db8::1']);

    expect($verifier->isFromAsaas('2002:db8::1'))->toBeFalse();
});

it('rejects values that are not IP addresses at all', function (): void {
    $verifier = new WebhookVerifier('token', ['not-an-ip']);

    expect($verifier->isFromAsaas('not-an-ip'))->toBeFalse();
    expect($verifier->isFromAsaas('52.67.12.206.7'))->toBeFalse();
    expect($verifier->isFromAsaas('10.0.0.1'))->toBeFalse();
});

it('exposes default IPs as a public constant', function (): void {
    expect(WebhookVerifier::DEFAULT_IPS)->toBe([
        '52.67.12.206',
        '18.230.8.159',
        '54.94.136.112',
        '54.94.183.101',
    ]);
});

// --- redaction ---

it('keeps the shared secret out of print_r and var_dump', function (): void {
    $verifier = new WebhookVerifier('wh_shared_secret');

    ob_start();
    var_dump($verifier);
    $dumped = (string) ob_get_clean();

    expect(print_r($verifier, true))->not->toContain('wh_shared_secret')
        ->and($dumped)->not->toContain('wh_shared_secret');
});

it('refuses to serialize', function (): void {
    // The verifier holds the secret that authenticates every inbound webhook,
    // and it is not an object anything legitimately caches or queues — unlike a
    // result, which is exempt for that reason.
    $verifier = new WebhookVerifier('wh_shared_secret');

    expect(fn (): string => serialize($verifier))
        ->toThrow(LogicException::class, WebhookVerifier::class.' cannot be serialized');
});

it('refuses to unserialize', function (): void {
    $verifier = new WebhookVerifier('wh_shared_secret');

    expect(function () use ($verifier): void {
        $verifier->__unserialize([]);
    })->toThrow(LogicException::class, WebhookVerifier::class.' cannot be unserialized.');
});

it('shows the trusted addresses while hiding the token', function (): void {
    $verifier = new WebhookVerifier('wh_shared_secret', ['52.67.12.206']);

    expect($verifier->__debugInfo())->toBe([
        'authToken' => '***',
        'trustedIps' => ['52.67.12.206'],
    ]);
});
