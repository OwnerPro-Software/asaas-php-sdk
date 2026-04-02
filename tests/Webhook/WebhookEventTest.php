<?php

declare(strict_types=1);

use OwnerPro\Asaas\Webhook\WebhookEvent;

mutates(WebhookEvent::class);

dataset('webhook_events', array_map(
    fn (WebhookEvent $case): array => [$case->name, $case->value],
    WebhookEvent::cases(),
));

it('has the correct value for each case', function (string $name, string $value): void {
    $case = WebhookEvent::from($value);

    expect($case->name)->toBe($name);
    expect($case->value)->toBe($value);
})->with('webhook_events');

it('has 111 cases', fn () => expect(WebhookEvent::cases())->toHaveCount(111));

it('throws for invalid string', fn () => WebhookEvent::from('INVALID'))->throws(ValueError::class);
