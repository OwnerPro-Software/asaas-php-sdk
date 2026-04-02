<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Webhook;

enum WebhookSendType: string
{
    case Sequentially = 'SEQUENTIALLY';
    case NonSequentially = 'NON_SEQUENTIALLY';
}
