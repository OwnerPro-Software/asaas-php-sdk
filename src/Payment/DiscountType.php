<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Payment;

enum DiscountType: string
{
    case Fixed = 'FIXED';
    case Percentage = 'PERCENTAGE';
}
