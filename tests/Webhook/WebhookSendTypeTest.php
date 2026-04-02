<?php

declare(strict_types=1);

use OwnerPro\Asaas\Webhook\WebhookSendType;

mutates(WebhookSendType::class);

it('has Sequentially case', fn () => expect(WebhookSendType::Sequentially->value)->toBe('SEQUENTIALLY'));
it('has NonSequentially case', fn () => expect(WebhookSendType::NonSequentially->value)->toBe('NON_SEQUENTIALLY'));

it('creates from valid string', fn () => expect(WebhookSendType::from('SEQUENTIALLY'))->toBe(WebhookSendType::Sequentially));

it('throws for invalid string', fn () => WebhookSendType::from('INVALID'))->throws(ValueError::class);
