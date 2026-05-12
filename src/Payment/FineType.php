<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

enum FineType: string
{
    case Fixed = 'FIXED';
    case Percentage = 'PERCENTAGE';
}
