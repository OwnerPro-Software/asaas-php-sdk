<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\PixAutomatic;

enum PixAutomaticAuthorizationStatus: string
{
    case Created = 'CREATED';
    case Active = 'ACTIVE';
    case Cancelled = 'CANCELLED';
    case Refused = 'REFUSED';
    case Expired = 'EXPIRED';
}
