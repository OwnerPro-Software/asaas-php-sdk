<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Tests\Architecture;

use OwnerPro\Asaas\Webhook\WebhookEvent;

/**
 * Webhook event coverage guard.
 *
 * Parses `specs/domains/webhooks.json` and extracts the `events` enum from the
 * `POST /v3/webhooks` request body. Compares it against the `WebhookEvent`
 * PHP enum cases.
 *
 * Fails when the spec adds events not mirrored in PHP, or when PHP carries
 * events the spec no longer recognises — both signal drift that needs a
 * coordinated SDK update.
 */
final class WebhookEventCoverageTest
{
    /**
     * @return list<string>
     */
    public static function specEvents(): array
    {
        $root = dirname(__DIR__, 2);
        $raw = (string) file_get_contents($root.'/specs/domains/webhooks.json');
        /** @var array<string, mixed> $spec */
        $spec = (array) json_decode($raw, true);

        /** @var list<string> $events */
        $events = $spec['paths']['/v3/webhooks']['post']['requestBody']['content']['application/json']['schema']['properties']['events']['items']['enum']
            ?? [];

        return $events;
    }

    /**
     * @return list<string>
     */
    public static function phpEvents(): array
    {
        return array_map(static fn (WebhookEvent $case): string => $case->value, WebhookEvent::cases());
    }
}

it('mirrors every spec webhook event in the PHP enum', function (): void {
    $missing = array_values(array_diff(
        WebhookEventCoverageTest::specEvents(),
        WebhookEventCoverageTest::phpEvents(),
    ));

    expect($missing)->toBe(
        [],
        "Spec adds webhook events not mirrored in OwnerPro\\Asaas\\Webhook\\WebhookEvent:\n  "
        .implode("\n  ", $missing),
    );
});

it('does not declare PHP webhook events the spec no longer recognises', function (): void {
    $extra = array_values(array_diff(
        WebhookEventCoverageTest::phpEvents(),
        WebhookEventCoverageTest::specEvents(),
    ));

    expect($extra)->toBe(
        [],
        "PHP enum carries webhook events not present in specs/domains/webhooks.json:\n  "
        .implode("\n  ", $extra),
    );
});

it('finds a non-empty events enum in the spec', function (): void {
    expect(WebhookEventCoverageTest::specEvents())->not->toBeEmpty();
});
